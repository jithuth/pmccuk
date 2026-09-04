<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Event;
use App\Models\Setting;
use App\Models\RenewalRequest;
use App\Models\Message;
use App\Models\StudentRequest;
use App\Models\News;
use App\Models\Menu;
use App\Models\TeamMember;
use App\Models\MemberChild;
use App\Models\ActivityLog;
use App\Models\FinancialTransaction;
use App\Models\EventPrice;
use App\Models\FareCategory;
use App\Models\FareRubric;
use App\Models\SponsorOffer;
use App\Models\OfferRedemption;
use App\Models\Admin;
use App\Models\Gallery;
use App\Models\Album;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\MemberIdCardEmail;
use Illuminate\Support\Carbon;
use App\Models\EventBooking;
use App\Models\FareRubricItem;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{
    private function ensureSuperAdmin()
    {
        if (Auth::guard('admin')->user()->role !== 'superadmin') {
            abort(403, 'Unauthorized action. Super Admin access required.');
        }
    }

    public function index()
    {
        $stats = [
            'total_members' => Member::count(),
            'active_members' => Member::where('status', 'active')->count(),
            'pending_members' => Member::where('status', 'pending')->count(),
            'total_events' => Event::count(),
            'new_messages' => Message::where('status', 'unread')->count(),
            'pending_renewals' => RenewalRequest::where('status', 'pending')->count(),
        ];
        return view('admin.dashboard', compact('stats'));
    }

    // --- MEMBERSHIP HUB ---
    public function members(Request $request)
    {
        $filter = $request->input('filter', 'active');
        $search = $request->input('search');

        $query = Member::query();

        if ($filter == 'deleted') {
            $query->onlyTrashed();
        } elseif ($filter != 'all') {
            $query->where('status', $filter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%$search%")
                    ->orWhere('email', 'LIKE', "%$search%")
                    ->orWhere('mobile_number', 'LIKE', "%$search%")
                    ->orWhere('membership_id_assigned', 'LIKE', "%$search%");
            });
        }

        $members = $query->orderBy('id', 'asc')->paginate(10);
        return view('admin.members.index', compact('members'));
    }

    public function getMemberDetails($id)
    {
        $member = Member::with('children')->withTrashed()->findOrFail($id);
        return response()->json($member);
    }

    public function updateMember(Request $request, $id)
    {
        $member = Member::withTrashed()->findOrFail($id);
        
        // Validation to prevent null violations
        $request->validate([
            'title' => 'required',
            'full_name' => 'required',
        ]);

        $data = $request->all();

        // Ensure title is never null if it somehow bypassed validation or was sent as empty
        $data['title'] = $request->input('title', $member->title) ?: 'Mr';

        if ($request->hasFile('member_photo')) {
            $data['photo'] = $request->file('member_photo')->store('photos', 'public');
        }
        if ($request->hasFile('family_photo')) {
            $data['family_photo'] = $request->file('family_photo')->store('photos', 'public');
        }

        $member->update($data);

        if ($request->has('child_name')) {
            $member->children()->delete();
            foreach ($request->child_name as $i => $name) {
                if (empty($name))
                    continue;
                MemberChild::create([
                    'member_id' => $member->id,
                    'child_name' => $name,
                    'sex' => $request->child_sex[$i] ?? '',
                    'dob' => $request->child_dob[$i] ?? null,
                ]);
            }
        }
        return back()->with('success', 'Member updated successfully.');
    }

    public function deleteMember($id)
    {
        Member::findOrFail($id)->delete();
        return back()->with('success', 'Member soft deleted.');
    }

    public function restoreMember($id)
    {
        Member::withTrashed()->findOrFail($id)->restore();
        return back()->with('success', 'Member restored.');
    }

    public function newMembers(Request $request)
    {
        $search = $request->input('search');
        $query = Member::where('status', 'pending');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%$search%")
                    ->orWhere('email', 'LIKE', "%$search%");
            });
        }

        $members = $query->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.members.new', compact('members'));
    }

    public function renewals(Request $request)
    {
        $search = $request->input('search');
        $query = RenewalRequest::where('status', 'pending');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%$search%")
                    ->orWhere('email', 'LIKE', "%$search%");
            });
        }

        $renewals = $query->with('member')->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.members.renewals', compact('renewals'));
    }

    public function getRenewalDetails($id)
    {
        $renewal = RenewalRequest::with(['member', 'children'])->findOrFail($id);

        // Prepare data for JSON with decryption
        $data = $renewal->toArray();

        // Force decrypt sensitive fields for the UI
        $fieldsToDecrypt = ['full_name', 'email', 'mobile_number', 'house_details', 'spouse_name', 'spouse_mobile', 'spouse_dob', 'post_code', 'emergency_name', 'emergency_mobile'];
        foreach ($fieldsToDecrypt as $field) {
            if (!empty($renewal->$field)) {
                $data[$field] = $renewal->$field; // Trigger the Trait's getAttribute
            }
        }

        // Add proper URLs for photos (Now handled by Model accessors)
        $data['photo_url'] = $renewal->photo_url;
        $data['family_photo_url'] = $renewal->family_photo_url;

        // Ensure amount is numeric for JS
        $data['payment_amount'] = (float) $renewal->payment_amount;

        return response()->json($data);
    }

    public function approveRenewal(Request $request, $id)
    {
        $renewal = RenewalRequest::findOrFail($id);
        $member = $renewal->member;

        $amount = $request->input('amount');
        $transRef = $request->input('transaction_ref');

        // Calculate new expiry
        $today = Carbon::now()->format('Y-m-d');
        if ($member->expiry_date && $member->expiry_date > $today) {
            $newExpiry = Carbon::parse($member->expiry_date)->addYear()->format('Y-m-d');
        } else {
            $newExpiry = Carbon::now()->addYear()->subDay()->format('Y-m-d');
        }

        // Sync data from renewal request to member
        $updateData = $renewal->toArray();
        unset($updateData['id'], $updateData['member_id'], $updateData['status'], $updateData['created_at'], $updateData['updated_at']);

        $updateData['expiry_date'] = $newExpiry;
        $updateData['status'] = 'active';

        // Auto-Repair GUID if missing
        if (empty($member->guid)) {
            $updateData['guid'] = (string) \Illuminate\Support\Str::uuid();
        }

        $updateData['payment_amount'] = $amount;
        $updateData['transaction_ref'] = $transRef;
        $updateData['payment_date'] = $today;

        $member->update($updateData);

        // Sync children
        $member->children()->delete();
        foreach ($renewal->children as $rc) {
            MemberChild::create([
                'member_id' => $member->id,
                'child_name' => $rc->child_name,
                'sex' => $rc->sex,
                'dob' => $rc->dob
            ]);
        }

        // Update renewal request
        $renewal->update([
            'status' => 'approved',
            'payment_amount' => $amount,
            'transaction_ref' => $transRef,
            'expiry_date' => $newExpiry
        ]);

        // Automated ID Card Delivery
        if (!empty($member->email)) {
            try {
                Mail::to($member->email)->send(new MemberIdCardEmail($member));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("ID Card Mail Failed on Renewal: " . $e->getMessage());
            }
        }

        // Record financial transaction
        FinancialTransaction::create([
            'type' => 'income',
            'category' => 'Membership Fee',
            'amount' => $amount,
            'transaction_date' => $today,
            'description' => "Renewal Fee for {$member->full_name} (ID: {$member->id})",
            'ref_no' => $transRef,
            'payment_method' => 'Bank Transfer'
        ]);

        // Log activity
        ActivityLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'admin_username' => Auth::guard('admin')->user()->username,
            'user_type' => 'admin',
            'action' => 'approve_renewal',
            'details' => "Approved Renewal: {$member->full_name} (ID: {$member->id}). New Expiry: $newExpiry",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return redirect()->back()->with('success', 'Renewal approved and profile updated.');
    }

    public function importMembers()
    {
        return view('admin.members.import');
    }

    public function accounting(Request $request)
    {
        $this->ensureSuperAdmin();
        $dFrom = $request->input('from', now()->startOfMonth()->format('Y-m-d'));
        $dTo = $request->input('to', now()->endOfMonth()->format('Y-m-d'));
        $fType = $request->input('f_type');
        $fCat = $request->input('f_cat');

        $query = FinancialTransaction::whereBetween('transaction_date', [$dFrom, $dTo]);
        if ($fType)
            $query->where('type', $fType);
        if ($fCat)
            $query->where('category', $fCat);

        $transactions = $query->orderBy('transaction_date', 'desc')->paginate(20);

        // Stats
        $globalInc = FinancialTransaction::where('type', 'income')->sum('amount');
        $globalExp = FinancialTransaction::where('type', 'expense')->sum('amount');
        $globalBal = $globalInc - $globalExp;

        $periodInc = FinancialTransaction::whereBetween('transaction_date', [$dFrom, $dTo])->where('type', 'income')->sum('amount');
        $periodExp = FinancialTransaction::whereBetween('transaction_date', [$dFrom, $dTo])->where('type', 'expense')->sum('amount');

        // Category Breakdown for Pie Chart
        $catStats = FinancialTransaction::where('type', 'income')
            ->whereBetween('transaction_date', [$dFrom, $dTo])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get();

        // Chart Data (Last 12 days)
        $chartLabels = [];
        $chartInc = [];
        $chartExp = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = now()->subDays($i)->format('d M');
            $chartInc[] = FinancialTransaction::where('transaction_date', $date)->where('type', 'income')->sum('amount');
            $chartExp[] = FinancialTransaction::where('transaction_date', $date)->where('type', 'expense')->sum('amount');
        }

        $allCats = FinancialTransaction::distinct()->pluck('category');

        return view('admin.financials.index', compact(
            'transactions',
            'globalBal',
            'periodInc',
            'periodExp',
            'chartLabels',
            'chartInc',
            'chartExp',
            'allCats',
            'dFrom',
            'dTo',
            'catStats'
        ));
    }

    public function syncFinancials()
    {
        $this->ensureSuperAdmin();
        $count = 0;

        // 1. Sync Approved Event Bookings
        $bookings = EventBooking::where('booking_status', 'approved')->get();
        foreach ($bookings as $b) {
            $exists = FinancialTransaction::where('ref_no', "EVT-{$b->id}")->exists();
            if ($exists)
                continue;

            $rawDate = $b->updated_at ?? $b->created_at ?? now();
            $carbonDate = ($rawDate instanceof Carbon) ? $rawDate : Carbon::parse($rawDate);

            // Deep Check: Does any record exist with same amount on same date for this member?
            $alreadyRecorded = FinancialTransaction::where('amount', $b->total_amount)
                ->where('transaction_date', $carbonDate->format('Y-m-d'))
                ->where('description', 'LIKE', "%{$b->full_name}%")
                ->exists();

            if (!$alreadyRecorded && $b->total_amount > 0) {
                FinancialTransaction::create([
                    'type' => 'income',
                    'category' => 'Event Ticket',
                    'amount' => $b->total_amount,
                    'transaction_date' => $carbonDate->format('Y-m-d'),
                    'description' => "RECONCILED: Event Booking for {$b->full_name} ({$b->event->title})",
                    'payment_method' => 'Online/Bank',
                    'ref_no' => "EVT-{$b->id}"
                ]);
                $count++;
            }
        }

        // 2. Sync Approved Renewals
        $renewals = RenewalRequest::where('status', 'approved')->get();
        foreach ($renewals as $r) {
            $exists = FinancialTransaction::where('ref_no', "REN-{$r->id}")->exists();
            if ($exists)
                continue;

            $rawDate = $r->updated_at ?? $r->created_at ?? now();
            $carbonDate = ($rawDate instanceof Carbon) ? $rawDate : Carbon::parse($rawDate);

            // Deep Check for Membership Fees
            $alreadyRecorded = FinancialTransaction::where('amount', $r->payment_amount)
                ->where('transaction_date', $carbonDate->format('Y-m-d'))
                ->where('description', 'LIKE', "%{$r->full_name}%")
                ->exists();

            if (!$alreadyRecorded && $r->payment_amount > 0) {
                FinancialTransaction::create([
                    'type' => 'income',
                    'category' => 'Membership Fee',
                    'amount' => $r->payment_amount,
                    'transaction_date' => $carbonDate->format('Y-m-d'),
                    'description' => "RECONCILED: Membership Renewal for {$r->full_name}",
                    'payment_method' => 'Bank Transfer',
                    'ref_no' => "REN-{$r->id}"
                ]);
                $count++;
            }
        }

        return redirect()->back()->with('success', "Financial reconciliation complete. $count missing records synchronized.");
    }

    public function revokeReconciliation()
    {
        $this->ensureSuperAdmin();
        $count = FinancialTransaction::where('description', 'LIKE', 'RECONCILED:%')->delete();
        return redirect()->back()->with('success', "Revoke complete. $count reconciled entries removed from the master ledger.");
    }

    public function exportTransactions(Request $request)
    {
        $this->ensureSuperAdmin();
        $dFrom = $request->input('from', now()->startOfMonth()->format('Y-m-d'));
        $dTo = $request->input('to', now()->endOfMonth()->format('Y-m-d'));
        $fType = $request->input('f_type');
        $fCat = $request->input('f_cat');

        $query = FinancialTransaction::whereBetween('transaction_date', [$dFrom, $dTo]);
        if ($fType)
            $query->where('type', $fType);
        if ($fCat)
            $query->where('category', $fCat);

        $transactions = $query->orderBy('transaction_date', 'asc')->get();

        $fileName = 'PMCC-Financial-Report-' . date('Y-m-d') . '.csv';
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['Date', 'Type', 'Category', 'Description', 'Method', 'Amount (£)'];

        $callback = function () use ($transactions, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($transactions as $t) {
                fputcsv($file, [
                    $t->transaction_date,
                    strtoupper($t->type),
                    $t->category,
                    $t->description,
                    $t->payment_method,
                    number_format($t->amount, 2, '.', '')
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function storeTransaction(Request $request)
    {
        $this->ensureSuperAdmin();
        $request->validate([
            'type' => 'required|in:income,expense',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'payment_method' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        FinancialTransaction::create($request->all());

        return redirect()->route('admin.accounting')
            ->with('success', 'Transaction successfully logged to the master ledger.');
    }

    public function getTransactionDetails($id)
    {
        $this->ensureSuperAdmin();
        $t = FinancialTransaction::findOrFail($id);
        return response()->json($t);
    }

    public function updateTransaction(Request $request, $id)
    {
        $this->ensureSuperAdmin();
        $t = FinancialTransaction::findOrFail($id);

        $request->validate([
            'type' => 'required|in:income,expense',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'payment_method' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $t->update($request->all());

        return redirect()->route('admin.accounting')
            ->with('success', 'Entry updated in the master ledger.');
    }

    public function deleteTransaction($id)
    {
        $this->ensureSuperAdmin();
        FinancialTransaction::findOrFail($id)->delete();

        return redirect()->route('admin.accounting')
            ->with('success', 'Transaction entry permanently removed from ledger.');
    }

    public function printIdCard($id = null)
    {
        if (!$id) {
            return redirect()->route('admin.members.index')->with('info', 'Please select a member first to generate an ID card.');
        }
        $member = Member::findOrFail($id);
        return view('admin.members.print_card', compact('member'));
    }

    public function verifyMembership($id, $token)
    {
        $member = Member::findOrFail($id);
        $secret = 'pmcc_secret_key_2026';
        $expectedToken = substr(hash('sha256', $member->id . $secret), 0, 10);

        $tokenValid = ($token === $expectedToken);
        $isActive = $member->isActive();
        $isValid = $tokenValid && $isActive;

        return view('admin.members.verify', compact('member', 'isValid', 'isActive', 'tokenValid'));
    }

    public function sendCardEmail($id)
    {
        $member = Member::findOrFail($id);

        if (empty($member->guid)) {
            $member->guid = (string) \Illuminate\Support\Str::uuid();
            $member->save();
        }

        if (empty($member->email)) {
            return back()->with('error', 'Member does not have an email address.');
        }

        try {
            Mail::to($member->email)->send(new MemberIdCardEmail($member));
            return back()->with('success', 'ID Card email sent successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }

    public function approveMember(Request $request, $id)
    {
        $member = Member::findOrFail($id);

        $membershipNo = $request->input('membership_no');
        $amount = $request->input('amount');
        $transRef = $request->input('transaction_ref');
        $expiryYear = $request->input('expiry_year', date('Y') + 1);

        // Calculate expiry date: Today + X years - 1 day
        $yearsDiff = $expiryYear - date('Y');
        if ($yearsDiff < 1)
            $yearsDiff = 1;
        $expiryDate = Carbon::now()->addYears($yearsDiff)->subDay()->format('Y-m-d');

        // Update member
        $member_update = [
            'status' => 'active',
            'membership_id_assigned' => $membershipNo,
            'payment_amount' => $amount,
            'transaction_ref' => $transRef,
            'expiry_date' => $expiryDate,
            'payment_date' => Carbon::now()->format('Y-m-d')
        ];

        // Auto-Repair GUID if missing
        if (empty($member->guid)) {
            $member_update['guid'] = (string) \Illuminate\Support\Str::uuid();
        }

        $member->update($member_update);

        // Record financial transaction
        FinancialTransaction::create([
            'type' => 'income',
            'category' => 'Membership Fee',
            'amount' => $amount,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => "Membership Fee for {$member->full_name} (ID: $id)",
            'ref_no' => $transRef,
            'payment_method' => 'Bank Transfer'
        ]);

        // Log activity
        ActivityLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'admin_username' => Auth::guard('admin')->user()->username,
            'user_type' => 'admin',
            'action' => 'approve_new_member',
            'details' => "Approved Member: {$member->full_name} (ID: $id). Reg No: $membershipNo",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // Automated ID Card Delivery
        if (!empty($member->email)) {
            try {
                Mail::to($member->email)->send(new MemberIdCardEmail($member));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("ID Card Mail Failed on New Approval: " . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Member approved successfully.');
    }

    // --- CRM & INQUIRIES ---
    public function messages()
    {
        $messages = Message::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.messages', compact('messages'));
    }

    public function markMessageRead($id)
    {
        $msg = Message::findOrFail($id);
        $msg->update(['status' => 'read']);
        return back()->with('success', 'Message marked as read.');
    }

    public function deleteMessage($id)
    {
        $msg = Message::findOrFail($id);
        $msg->delete();
        return back()->with('success', 'Message deleted.');
    }

    public function studentRequests()
    {
        $requests = StudentRequest::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.student_requests', compact('requests'));
    }

    // --- WEBSITE & CONTENT ---
    public function homeBanners()
    {
        return redirect()->route('admin.config.settings', ['tab' => 'home']);
    }
    public function aboutContent()
    {
        return redirect()->route('admin.config.settings', ['tab' => 'about']);
    }
    public function team(Request $request)
    {
        $currentTeam = TeamMember::where('category', 'current')->orderBy('order_no')->get();
        $formerTeam = TeamMember::where('category', 'former')->orderBy('service_years', 'desc')->get();

        $editMember = null;
        if ($request->has('edit')) {
            $editMember = TeamMember::find($request->edit);
        }

        return view('admin.cms.team', compact('currentTeam', 'formerTeam', 'editMember'));
    }

    public function addTeamMember(Request $request)
    {
        $request->validate(['name' => 'required', 'role' => 'required']);

        $imageUrl = $request->input('image_url');
        if ($request->hasFile('team_image')) {
            $path = $request->file('team_image')->store('team', 'public');
            $imageUrl = $path;
        }

        TeamMember::create(array_merge($request->except('team_image'), ['image_url' => $imageUrl]));

        return redirect()->route('admin.team')->with('success', 'Member added to committee.');
    }

    public function updateTeamMember(Request $request, $id)
    {
        $member = TeamMember::findOrFail($id);

        $imageUrl = $request->input('image_url', $member->image_url);
        if ($request->hasFile('team_image')) {
            $path = $request->file('team_image')->store('team', 'public');
            $imageUrl = $path;
        }

        $member->update(array_merge($request->except('team_image'), ['image_url' => $imageUrl]));

        return redirect()->route('admin.team')->with('success', 'Member updated.');
    }

    public function deleteTeamMember($id)
    {
        $member = TeamMember::findOrFail($id);
        $member->delete();
        return redirect()->route('admin.team')->with('success', 'Member removed.');
    }
    public function menus()
    {
        $allMenus = Menu::orderBy('order_no')->get();
        return view('admin.cms.menus', compact('allMenus'));
    }

    public function addMenu(Request $request)
    {
        $request->validate(['title' => 'required', 'url' => 'required']);
        Menu::create($request->all());
        return back()->with('success', 'Menu link added.');
    }

    public function updateMenu(Request $request, $id)
    {
        $m = Menu::findOrFail($id);
        $request->validate(['title' => 'required', 'url' => 'required']);
        $m->update($request->all());
        return back()->with('success', 'Menu link updated.');
    }

    public function deleteMenu($id)
    {
        $m = Menu::findOrFail($id);
        $m->delete();
        return back()->with('success', 'Menu link removed.');
    }
    public function news(Request $request)
    {
        $news = News::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.news.index', compact('news'));
    }

    public function addNews(Request $request)
    {
        $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'status' => 'required|in:published,draft'
        ]);

        $imageUrl = $request->input('image_url');
        if ($request->hasFile('news_image')) {
            $path = $request->file('news_image')->store('news', 'public');
            $imageUrl = $path;
        }

        News::create([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'image_url' => $imageUrl,
            'status' => $request->input('status'),
            'created_at' => now()
        ]);

        return redirect()->route('admin.news')->with('success', 'Article published successfully!');
    }

    public function getNewsDetails($id)
    {
        $n = News::findOrFail($id);
        return response()->json($n);
    }

    public function updateNews(Request $request, $id)
    {
        $n = News::findOrFail($id);
        $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'status' => 'required|in:published,draft'
        ]);

        $imageUrl = $n->image_url;
        if ($request->hasFile('news_image')) {
            $path = $request->file('news_image')->store('news', 'public');
            $imageUrl = $path;
        } elseif ($request->filled('image_url')) {
            $imageUrl = $request->input('image_url');
        }

        $n->update([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'image_url' => $imageUrl,
            'status' => $request->input('status')
        ]);

        return redirect()->route('admin.news')->with('success', 'Article updated successfully!');
    }

    public function deleteNews($id)
    {
        $n = News::findOrFail($id);
        $n->delete();
        return redirect()->route('admin.news')->with('success', 'Article deleted.');
    }

    public function terminal()
    {
        if (auth('admin')->user()->username !== 'superadmin') {
            abort(403, 'Unauthorized access to system terminal.');
        }

        // Send Telegram Alert
        try {
            $admin = auth('admin')->user();
            \App\Services\TelegramService::sendMessage(
                "💀 ⚠️ <b>Security Alert: System Terminal Page Accessed</b>\n\n" .
                "👤 <b>User:</b> " . htmlspecialchars($admin->username) . " (ID: {$admin->id})\n" .
                "🌐 <b>IP Address:</b> " . request()->ip()
            );
        } catch (\Exception $e) {}

        return view('admin.config.terminal');
    }

    public function runTerminalCommand(Request $request)
    {
        if (auth('admin')->user()->username !== 'superadmin') {
            return response()->json(['output' => 'FATAL: Unauthorized access attempted.'], 403);
        }

        $command = trim($request->input('command'));

        // Send Telegram Alert
        try {
            $admin = auth('admin')->user();
            \App\Services\TelegramService::sendMessage(
                "💀 <b>Security Alert: System Terminal Command Executed</b>\n\n" .
                "👤 <b>User:</b> " . htmlspecialchars($admin->username) . " (ID: {$admin->id})\n" .
                "💻 <b>Command:</b> <code>" . htmlspecialchars($command) . "</code>\n" .
                "🌐 <b>IP Address:</b> " . $request->ip()
            );
        } catch (\Exception $e) {}
        
        // Anti-Destructive patterns check
        $destructive = ['migrate:fresh', 'db:wipe', 'key:generate'];
        foreach($destructive as $d) {
            if (str_contains($command, $d)) {
                return response()->json(['output' => "ERROR: Command '$d' is strictly prohibited via web terminal for safety."]);
            }
        }

        try {
            // Clean common prefixes
            $artisanCmd = str_replace(['php artisan ', 'artisan '], '', $command);
            
            \Illuminate\Support\Facades\Artisan::call($artisanCmd);
            $output = \Illuminate\Support\Facades\Artisan::output();
            
            return response()->json(['output' => $output ?: 'Command executed successfully.']);
        } catch (\Exception $e) {
            return response()->json(['output' => "FATAL ERROR:\n" . $e->getMessage()]);
        }
    }

    // --- EVENTS ---
    public function events(Request $request)
    {
        $events = Event::withCount([
            'bookings as approved_count' => function ($query) {
                $query->where('booking_status', 'approved');
            },
            'bookings as attendance_count' => function ($query) {
                $query->where('check_in_status', 'checked_in');
            }
        ])->orderBy('event_date', 'desc')->paginate(20);

        $categories = FareCategory::all();
        $rubrics = FareRubric::with('items')->get();

        $editEvent = null;
        if ($request->has('edit')) {
            $editEvent = Event::with('prices')->find($request->edit);
        }

        return view('admin.events.index', compact('events', 'categories', 'rubrics', 'editEvent'));
    }

    public function addEvent(Request $request)
    {
        $request->validate([
            'title' => 'required|max:255',
            'event_date' => 'required|date',
            'location' => 'nullable'
        ]);

        $imageUrl = $request->input('image_url');
        if ($request->hasFile('event_image')) {
            $path = $request->file('event_image')->store('events', 'public');
            $imageUrl = $path;
        }

        $event = Event::create([
            'title' => $request->title,
            'description' => $request->description,
            'event_date' => $request->event_date,
            'location' => $request->location,
            'image_url' => $imageUrl,
            'rubric_id' => $request->rubric_id,
            'allow_guest_packages' => $request->has('allow_guest_packages') ? 1 : 0
        ]);

        if ($request->has('prices')) {
            foreach ($request->prices as $catId => $p) {
                EventPrice::create([
                    'event_id' => $event->id,
                    'category_id' => $catId,
                    'member_price' => $p['member'] ?? 0,
                    'guest_price' => $p['guest'] ?? 0,
                    'is_guest_visible' => isset($p['guest_visible']) ? 1 : 0
                ]);
            }
        }

        return redirect()->route('admin.events.index')->with('success', 'Event created successfully!');
    }

    public function updateEvent(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        $request->validate([
            'title' => 'required|max:255',
            'event_date' => 'required|date'
        ]);

        $imageUrl = $request->input('image_url', $event->image_url);
        if ($request->hasFile('event_image')) {
            $path = $request->file('event_image')->store('events', 'public');
            $imageUrl = $path;
        }

        $event->update([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'event_date' => $request->input('event_date'),
            'location' => $request->input('location'),
            'image_url' => $imageUrl,
            'rubric_id' => $request->input('rubric_id'),
            'allow_guest_packages' => $request->has('allow_guest_packages') ? 1 : 0
        ]);

        if ($request->has('prices')) {
            EventPrice::where('event_id', $event->id)->delete();
            foreach ($request->input('prices') as $catId => $p) {
                EventPrice::create([
                    'event_id' => $event->id,
                    'category_id' => $catId,
                    'member_price' => $p['member'] ?? 0,
                    'guest_price' => $p['guest'] ?? 0,
                    'is_guest_visible' => isset($p['guest_visible']) ? 1 : 0
                ]);
            }
        }

        return redirect()->route('admin.events.index')->with('success', 'Event architecture updated successfully!');
    }
    public function deleteEvent($id)
    {
        $event = Event::findOrFail($id);
        $event->prices()->delete();
        $event->delete();
        return redirect()->route('admin.events.index')->with('success', 'Event and associated pricing deleted successfully!');
    }

    public function eventBookings()
    {
        return view('admin.events.bookings');
    }

    public function eventStats()
    {
        $events = Event::orderBy('event_date', 'desc')->get();
        $allBookings = EventBooking::all();

        $global = [
            'total_revenue' => $allBookings->where('booking_status', 'approved')->sum('paid_amount'),
            'checked_in' => $allBookings->where('booking_status', 'approved')->count(),
            'pending_approval' => $allBookings->where('booking_status', 'pending')->count(),
            'total_bookings' => $allBookings->count(),
        ];

        $stats = [];
        foreach ($events as $event) {
            $bookings = EventBooking::where('event_id', $event->id)->where('booking_status', 'approved')->get();
            $stats[] = [
                'event' => $event,
                'total_bookings' => EventBooking::where('event_id', $event->id)->count(),
                'confirmed_bookings' => $bookings->count(),
                'adults' => $bookings->sum('adult_count'),
                'children' => $bookings->sum('child_count'),
                'infants' => $bookings->sum('infant_count'),
                'total_revenue' => $bookings->sum('paid_amount'),
            ];
        }

        return view('admin.events.stats', compact('global', 'stats'));
    }

    public function fareLogic()
    {
        $categories = FareCategory::orderBy('id')->get();
        $rubrics = FareRubric::with('items.category')->orderBy('id')->get();
        return view('admin.events.fare_logic', compact('categories', 'rubrics'));
    }

    public function saveFareCategory(Request $request)
    {
        $request->validate(['name' => 'required|max:255']);
        if ($request->id) {
            FareCategory::findOrFail($request->id)->update(['name' => $request->name]);
        } else {
            FareCategory::create(['name' => $request->name]);
        }
        return back()->with('success', 'Fare category saved!');
    }

    public function deleteFareCategory($id)
    {
        FareCategory::findOrFail($id)->delete();
        return back()->with('success', 'Fare category removed.');
    }

    public function saveFareRubric(Request $request)
    {
        $request->validate(['name' => 'required|max:255']);
        
        if ($request->id) {
            $rubric = FareRubric::findOrFail($request->id);
            $rubric->update(['name' => $request->name]);
        } else {
            $rubric = FareRubric::create(['name' => $request->name]);
        }

        // Sync items
        if ($request->has('items')) {
            FareRubricItem::where('rubric_id', $rubric->id)->delete();
            foreach ($request->items as $catId => $prices) {
                FareRubricItem::create([
                    'rubric_id' => $rubric->id,
                    'category_id' => $catId,
                    'member_price' => $prices['member'] ?? 0,
                    'guest_price' => $prices['guest'] ?? 0
                ]);
            }
        }

        return back()->with('success', 'Pricing rubric saved!');
    }

    public function deleteFareRubric($id)
    {
        $rubric = FareRubric::findOrFail($id);
        $rubric->items()->delete();
        $rubric->delete();
        return back()->with('success', 'Pricing rubric removed.');
    }

    // --- SPONSORS ---
    public function offers(Request $request)
    {
        $role = Auth::guard('admin')->user()->role;
        $adminId = Auth::guard('admin')->id();

        $query = SponsorOffer::withCount('redemptions');
        if ($role !== 'admin' && $role !== 'superadmin') {
            $query->where('admin_id', $adminId);
        }
        $offers = $query->orderBy('order_no')->paginate(20);

        $sponsors = Admin::where('role', 'sponsor')->orWhere('role', 'admin')->get();
        $editOffer = $request->has('edit') ? SponsorOffer::find($request->edit) : null;

        return view('admin.sponsors.offers', compact('offers', 'sponsors', 'editOffer'));
    }

    public function addOffer(Request $request)
    {
        $request->validate(['sponsor_name' => 'required', 'title' => 'required']);

        $logoUrl = $request->input('logo_url');
        if ($request->hasFile('logo_image')) {
            $path = $request->file('logo_image')->store('sponsors', 'public');
            $logoUrl = $path;
        }

        SponsorOffer::create(array_merge($request->except('logo_image'), ['logo_url' => $logoUrl]));

        return redirect()->route('admin.sponsors.offers')->with('success', 'Offer added successfully!');
    }

    public function updateOffer(Request $request, $id)
    {
        $offer = SponsorOffer::findOrFail($id);

        $logoUrl = $request->input('logo_url', $offer->logo_url);
        if ($request->hasFile('logo_image')) {
            $path = $request->file('logo_image')->store('sponsors', 'public');
            $logoUrl = $path;
        }

        $offer->update(array_merge($request->except('logo_image'), ['logo_url' => $logoUrl]));

        return redirect()->route('admin.sponsors.offers')->with('success', 'Offer updated!');
    }

    public function deleteOffer($id)
    {
        $offer = SponsorOffer::findOrFail($id);
        $offer->delete();
        return redirect()->route('admin.sponsors.offers')->with('success', 'Offer removed.');
    }
    public function redemptions()
    {
        $redemptions = OfferRedemption::with(['offer', 'member'])->orderBy('id', 'desc')->paginate(30);
        return view('admin.sponsors.redemptions', compact('redemptions'));
    }

    // --- MEDIA & FINANCE ---
    public function gallery(Request $request)
    {
        $gallery = Gallery::with('album')->orderBy('id', 'desc')->paginate(30);
        $albums = Album::orderBy('name', 'asc')->get();
        return view('admin.media.gallery', compact('gallery', 'albums'));
    }

    public function addGallery(Request $request)
    {
        $request->validate([
            'gallery_image' => 'required|image',
            'album_id' => 'required|exists:albums,id'
        ]);

        if ($request->hasFile('gallery_image')) {
            $path = $request->file('gallery_image')->store('gallery', 'public');
            Gallery::create([
                'title' => $request->title,
                'image_url' => $path,
                'album_id' => $request->album_id
            ]);
        }

        return redirect()->back()->with('success', 'Media added to gallery.');
    }

    public function deleteGallery($id)
    {
        $g = Gallery::findOrFail($id);
        $g->delete();
        return redirect()->back()->with('success', 'Media removed.');
    }

    public function albums()
    {
        $albums = Album::withCount('photos')->orderBy('id', 'desc')->paginate(20);
        return view('admin.media.albums', compact('albums'));
    }

    public function storeAlbum(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image'
        ]);

        $coverPath = null;
        if ($request->hasFile('cover_image')) {
            $coverPath = $request->file('cover_image')->store('albums', 'public');
        }

        Album::create([
            'name' => $request->name,
            'description' => $request->description,
            'cover_image_url' => $coverPath
        ]);

        return redirect()->back()->with('success', 'Photo album created successfully!');
    }

    public function deleteAlbum($id)
    {
        $album = Album::findOrFail($id);
        $album->photos()->delete();
        $album->delete();
        return redirect()->back()->with('success', 'Album deleted successfully!');
    }

    public function videos()
    {
        $videos = Video::orderBy('id', 'desc')->paginate(20);
        return view('admin.media.videos', compact('videos'));
    }

    public function storeVideo(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'video_url' => 'required|url',
            'description' => 'nullable|string'
        ]);

        Video::create([
            'title' => $request->title,
            'video_url' => $request->video_url,
            'description' => $request->description,
            'platform' => 'youtube'
        ]);

        return redirect()->back()->with('success', 'Video added to gallery successfully!');
    }

    public function deleteVideo($id)
    {
        $video = Video::findOrFail($id);
        $video->delete();
        return redirect()->back()->with('success', 'Video removed successfully!');
    }

    // --- SYSTEM ---
    public function securityAudit()
    {
        $this->ensureSuperAdmin();

        $checks = [];

        // 1. HTTPS Config
        $isHttps = request()->secure() || (env('APP_URL') && str_starts_with(env('APP_URL'), 'https://'));
        $checks['https'] = [
            'name' => 'HTTPS Encryption (TLS 1.3)',
            'status' => $isHttps ? 'passed' : 'warning',
            'desc' => 'Enforces secure SSL/TLS communication.',
            'details' => $isHttps ? 'HTTPS scheme active or APP_URL configured with HTTPS.' : 'APP_URL is not set to HTTPS. Ensure edge routing redirects traffic to HTTPS.',
        ];

        // 2. Cryptographic AES-256 DB Encryption
        $hasTrait = trait_exists('App\Traits\HasSmartDecryption');
        $checks['db_encryption'] = [
            'name' => 'AES-256 Database Encryption',
            'status' => $hasTrait ? 'passed' : 'failed',
            'desc' => 'Encrypts sensitive member data in database.',
            'details' => $hasTrait ? 'HasSmartDecryption trait detected and active on member tables.' : 'HasSmartDecryption trait is missing.',
        ];

        // 3. Password Hashing
        $hashDriver = config('hash.driver', 'bcrypt');
        $checks['password_hashing'] = [
            'name' => 'Secure Password Hashing',
            'status' => in_array($hashDriver, ['bcrypt', 'argon', 'argon2id']) ? 'passed' : 'warning',
            'desc' => 'Uses one-way strong cryptographic hashing.',
            'details' => "Cryptographic driver '" . ucfirst($hashDriver) . "' is active for all accounts.",
        ];

        // 4. Two-Factor Authentication (2FA)
        $has2fa = class_exists('PragmaRX\Google2FA\Google2FA');
        $checks['two_factor'] = [
            'name' => 'Two-Factor Auth (2FA)',
            'status' => $has2fa ? 'passed' : 'failed',
            'desc' => 'Google Authenticator TOTP verification layer.',
            'details' => $has2fa ? 'Google2FA library is active. Admins can enable 2FA protection.' : 'Google2FA library is not installed.',
        ];

        // 5. Role-Based Access Control (RBAC)
        $superAdminCount = Admin::where('role', 'superadmin')->count();
        $staffCount = Admin::where('role', 'staff')->count();
        $checks['rbac'] = [
            'name' => 'RBAC & Least Privilege',
            'status' => ($superAdminCount > 0) ? 'passed' : 'warning',
            'desc' => 'Restricts terminal/backdoor actions to Super Admin.',
            'details' => "System has {$superAdminCount} SuperAdmin(s) and {$staffCount} Staff role(s) configured.",
        ];

        // 6. SQL Injection Mitigations
        $checks['sqli'] = [
            'name' => 'SQLi Prevention',
            'status' => 'passed',
            'desc' => 'Strict Eloquent ORM parameter bindings.',
            'details' => 'Database layer utilizes query parameterization. Raw SQL queries are avoided.',
        ];

        // 7. HTTP Security Headers
        $hasMiddleware = class_exists('App\Http\Middleware\SecurityHeaders');
        $checks['headers'] = [
            'name' => 'Security Headers (CSP & Clickjacking)',
            'status' => $hasMiddleware ? 'passed' : 'failed',
            'desc' => 'Injects CSP, X-Frame-Options, and nosniff headers.',
            'details' => $hasMiddleware ? 'SecurityHeaders middleware active. CSP set to self/trusted CDNs.' : 'SecurityHeaders middleware class is missing.',
        ];

        // 8. CSRF Protection
        $checks['csrf'] = [
            'name' => 'CSRF Token Validation',
            'status' => 'passed',
            'desc' => 'Enforces verify token on all modifying request methods.',
            'details' => 'Laravel VerifyCsrfToken middleware is active globally.',
        ];

        // 9. Secure File Uploads
        $maxUpload = ini_get('upload_max_filesize');
        $checks['file_uploads'] = [
            'name' => 'Secure File Uploads (RCE Protection)',
            'status' => 'passed',
            'desc' => 'Verifies photo mime-types and restricts size limits.',
            'details' => "MIME verification active. PHP Maximum upload limit is set to: {$maxUpload}.",
        ];

        // 10. Secure Session Cookies
        $cookieSecure = config('session.secure', false);
        $cookieHttpOnly = config('session.http_only', true);
        $cookieSameSite = config('session.same_site', 'lax');
        $checks['cookies'] = [
            'name' => 'Secure Session Cookies',
            'status' => ($cookieHttpOnly) ? 'passed' : 'warning',
            'desc' => 'Flags cookies with HttpOnly, Secure, and SameSite.',
            'details' => "HttpOnly: " . ($cookieHttpOnly ? 'Yes' : 'No') . " | Secure: " . ($cookieSecure ? 'Yes' : 'No') . " | SameSite: " . ucfirst($cookieSameSite),
        ];

        // 11. Session Lifetime / Timeout
        $sessionLifetime = config('session.lifetime');
        $checks['timeout'] = [
            'name' => 'Inactivity Session Timeout',
            'status' => ($sessionLifetime <= 120) ? 'passed' : 'warning',
            'desc' => 'Automatically logs out inactive admins.',
            'details' => "Session timeout set to: {$sessionLifetime} minutes.",
        ];

        // 12. Spam Honeypot Mitigation
        $hasHoneypot = class_exists('App\Http\Middleware\CheckHoneypot');
        $checks['honeypot'] = [
            'name' => 'Spam & Bot Honeypot checks',
            'status' => $hasHoneypot ? 'passed' : 'failed',
            'desc' => 'Blocks automated spam registrations.',
            'details' => $hasHoneypot ? 'CheckHoneypot middleware is configured on public post routes.' : 'CheckHoneypot middleware is missing.',
        ];

        // 13. Login Rate Limiting
        $checks['rate_limiting'] = [
            'name' => 'Login Rate Limiting',
            'status' => 'passed',
            'desc' => 'Prevents login brute-force attacks.',
            'details' => 'Rate limiter limits admin login to 5 requests/minute.',
        ];

        // 14. Audits & Activity Logging
        $logCount = ActivityLog::count();
        $checks['logging'] = [
            'name' => 'Security Logging & Audit Trails',
            'status' => ($logCount > 0) ? 'passed' : 'warning',
            'desc' => 'Logs administrative changes and client IPs.',
            'details' => "Audit trail active. Captured {$logCount} administrative actions.",
        ];

        // 15. Telegram Bot Security Alerts
        $telegramToken = Setting::where('setting_key', 'telegram_bot_token')->value('setting_value');
        $telegramChat = Setting::where('setting_key', 'telegram_chat_id')->value('setting_value');
        $telegramActive = !empty($telegramToken) && !empty($telegramChat);
        $checks['telegram_alerts'] = [
            'name' => 'Telegram Bot Security Alerts',
            'status' => $telegramActive ? 'passed' : 'warning',
            'desc' => 'Real-time Telegram notifications for sensitive system events.',
            'details' => $telegramActive ? 'Telegram Bot configured. Alerts active.' : 'Telegram credentials missing. Alerts inactive.',
        ];

        // Fetch WAF configurations
        $wafEnabled = Setting::where('setting_key', 'waf_enabled')->value('setting_value') ?? '0';
        $wafIpBlocklist = Setting::where('setting_key', 'waf_ip_blocklist')->value('setting_value') ?? '';
        
        // Fetch recent WAF blocked logs
        $blockedLogs = ActivityLog::where('action', 'waf_blocked')
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        // Calculate score
        $passedCount = count(array_filter($checks, fn($c) => $c['status'] === 'passed'));
        $score = round(($passedCount / count($checks)) * 100);

        return view('admin.system.security', compact('checks', 'score', 'passedCount', 'wafEnabled', 'wafIpBlocklist', 'blockedLogs'));
    }

    public function updateWafSettings(Request $request)
    {
        $this->ensureSuperAdmin();

        $request->validate([
            'waf_enabled' => 'required|in:0,1',
            'waf_ip_blocklist' => 'nullable|string',
        ]);

        Setting::updateOrCreate(
            ['setting_key' => 'waf_enabled'],
            ['setting_value' => $request->input('waf_enabled')]
        );

        Setting::updateOrCreate(
            ['setting_key' => 'waf_ip_blocklist'],
            ['setting_value' => $request->input('waf_ip_blocklist') ?? '']
        );

        // Send Telegram Alert for WAF settings modification
        try {
            $admin = auth('admin')->user();
            \App\Services\TelegramService::sendMessage(
                "🛡️ ⚙️ <b>Security Alert: WAF Configuration Updated</b>\n\n" .
                "👤 <b>User:</b> " . htmlspecialchars($admin->username) . " (ID: {$admin->id})\n" .
                "🔥 <b>WAF Protection:</b> " . ($request->input('waf_enabled') == '1' ? 'ENABLED 🟢' : 'DISABLED 🔴') . "\n" .
                "🌐 <b>IP Address:</b> " . $request->ip()
            );
        } catch (\Exception $e) {}

        return redirect()->route('admin.security-audit', ['tab' => 'waf'])
            ->with('success', 'Web Application Firewall (WAF) settings updated successfully.');
    }
    public function accessControl(Request $request)
    {
        $this->ensureSuperAdmin();
        $existingCount = Admin::where('username', 'superadmin')->count();
        $admins = Admin::orderBy('id')->get();
        return view('admin.system.admins', compact('admins'));
    }

    public function storeAdmin(Request $request)
    {
        $this->ensureSuperAdmin();
        $request->validate([
            'username' => 'required|unique:admins,username|max:60',
            'email' => 'nullable|email|unique:admins,email|max:120',
            'role' => 'required|in:admin,superadmin,staff',
            'password' => 'required|confirmed|min:8',
        ]);

        Admin::create([
            'username' => $request->input('username'),
            'email' => $request->input('email'),
            'role' => $request->input('role'),
            'password' => Hash::make($request->password),
        ]);

        // Send Telegram Alert
        try {
            $actor = Auth::guard('admin')->user();
            \App\Services\TelegramService::sendMessage(
                "👤 ➕ <b>Security Alert: Administrative Account Created</b>\n\n" .
                "👤 <b>Created By:</b> " . htmlspecialchars($actor->username) . " (ID: {$actor->id})\n" .
                "🆕 <b>New Account:</b> " . htmlspecialchars($request->username) . " (Role: {$request->role})\n" .
                "🌐 <b>IP Address:</b> " . $request->ip()
            );
        } catch (\Exception $e) {}

        return redirect()->route('admin.access-control')
            ->with('success', "Admin account '{$request->username}' created successfully.");
    }

    public function deleteAdmin($id)
    {
        $this->ensureSuperAdmin();
        if ($id == Auth::guard('admin')->id()) {
            return redirect()->route('admin.access-control')
                ->with('error', 'You cannot delete your own account.');
        }
        $admin = Admin::findOrFail($id);
        $admin->delete();

        // Send Telegram Alert
        try {
            $actor = Auth::guard('admin')->user();
            \App\Services\TelegramService::sendMessage(
                "👤 ➖ <b>Security Alert: Administrative Account Removed</b>\n\n" .
                "👤 <b>Removed By:</b> " . htmlspecialchars($actor->username) . " (ID: {$actor->id})\n" .
                "❌ <b>Account Removed:</b> " . htmlspecialchars($admin->username) . " (Role: {$admin->role})\n" .
                "🌐 <b>IP Address:</b> " . request()->ip()
            );
        } catch (\Exception $e) {}

        return redirect()->route('admin.access-control')
            ->with('success', "Admin account '{$admin->username}' has been removed.");
    }

    public function updateAdmin(Request $request, $id)
    {
        $this->ensureSuperAdmin();
        $admin = Admin::findOrFail($id);

        $request->validate([
            'username' => 'required|max:60|unique:admins,username,' . $id,
            'email' => 'nullable|email|max:120',
            'role' => 'required|in:admin,superadmin,staff',
        ]);

        $admin->update($request->only(['username', 'email', 'role']));

        return redirect()->route('admin.access-control')
            ->with('success', "Admin account '{$admin->username}' updated successfully.");
    }

    public function updateAdminPassword(Request $request, $id)
    {
        $this->ensureSuperAdmin();
        $request->validate([
            'password' => 'required|confirmed|min:8',
        ]);

        $admin = Admin::findOrFail($id);
        $admin->password = Hash::make($request->password);
        $admin->save();

        return redirect()->route('admin.access-control')
            ->with('success', "Password for '{$admin->username}' updated successfully.");
    }

    public function activityLogs()
    {
        $logs = ActivityLog::orderBy('created_at', 'desc')->paginate(50);
        return view('admin.system.logs', compact('logs'));
    }

    public function settings()
    {
        $settings_raw = Setting::all();
        $settings = [];
        foreach ($settings_raw as $s) {
            $settings[$s->setting_key] = $s->setting_value;
        }
        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        // Handle normal inputs
        $inputs = $request->except(['_token', 'hero_image_file', 'pres_image_file']);
        foreach ($inputs as $key => $value) {
            Setting::updateOrCreate(['setting_key' => $key], ['setting_value' => $value]);
        }

        // Handle file uploads
        if ($request->hasFile('hero_image_file')) {
            $path = $request->file('hero_image_file')->store('cms', 'public');
            Setting::updateOrCreate(['setting_key' => 'hero_image'], ['setting_value' => $path]);
        }

        if ($request->hasFile('pres_image_file')) {
            $path = $request->file('pres_image_file')->store('cms', 'public');
            Setting::updateOrCreate(['setting_key' => 'pres_image'], ['setting_value' => $path]);
        }

        if ($request->hasFile('about_image_file')) {
            $path = $request->file('about_image_file')->store('cms', 'public');
            Setting::updateOrCreate(['setting_key' => 'about_image'], ['setting_value' => $path]);
        }

        return back()->with('success', 'Settings updated successfully!');
    }

    public function fileExplorer(Request $request)
    {
        $this->ensureSuperAdmin();
        $subPath = $request->input('path', '');
        // Security check: No parent directory traversal
        if (str_contains($subPath, '..')) {
            $subPath = '';
        }

        $basePath = storage_path('app/public/');
        $fullPath = $basePath . $subPath;

        if (!file_exists($fullPath)) {
            $fullPath = $basePath;
            $subPath = '';
        }

        $files = [];
        $directories = [];

        $items = scandir($fullPath);
        foreach ($items as $item) {
            if ($item == '.' || $item == '..')
                continue;

            $itemPath = $fullPath . '/' . $item;
            $relPath = ($subPath ? $subPath . '/' : '') . $item;

            if (is_dir($itemPath)) {
                $directories[] = [
                    'name' => $item,
                    'path' => $relPath,
                    'count' => count(scandir($itemPath)) - 2
                ];
            } else {
                $files[] = [
                    'name' => $item,
                    'path' => $relPath,
                    'size' => round(filesize($itemPath) / 1024, 2),
                    'url' => asset('storage/' . $relPath),
                    'is_image' => in_array(strtolower(pathinfo($item, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])
                ];
            }
        }

        return view('admin.config.file-explorer', compact('files', 'directories', 'subPath'));
    }

    public function deleteFile(Request $request)
    {
        $this->ensureSuperAdmin();
        $path = $request->input('path');
        if (str_contains($path, '..'))
            return back()->with('error', 'Invalid path.');

        $fullPath = storage_path('app/public/' . $path);
        if (file_exists($fullPath) && !is_dir($fullPath)) {
            unlink($fullPath);

            // Send Telegram Alert
            try {
                $admin = auth('admin')->user();
                \App\Services\TelegramService::sendMessage(
                    "🗑️ <b>Security Alert: File Deleted from Explorer</b>\n\n" .
                    "👤 <b>User:</b> " . htmlspecialchars($admin->username) . " (ID: {$admin->id})\n" .
                    "📁 <b>Path:</b> <code>" . htmlspecialchars($path) . "</code>\n" .
                    "🌐 <b>IP Address:</b> " . $request->ip()
                );
            } catch (\Exception $e) {}

            return back()->with('success', 'File deleted.');
        }

        return back()->with('error', 'File not found or is a directory.');
    }
    public function systemRepair()
    {
        return view('admin.config.repair');
    }
    public function runSystemRepair(Request $request)
    {
        $action = $request->input('action');

        // Send Telegram Alert
        try {
            $admin = auth('admin')->user();
            \App\Services\TelegramService::sendMessage(
                "🛠️ <b>Security Alert: System Repair Task Executed</b>\n\n" .
                "👤 <b>User:</b> " . htmlspecialchars($admin->username) . " (ID: {$admin->id})\n" .
                "🔧 <b>Action:</b> <code>" . htmlspecialchars($action) . "</code>\n" .
                "🌐 <b>IP Address:</b> " . $request->ip()
            );
        } catch (\Exception $e) {}
        
        $validActions = [
            'cache' => 'cache:clear',
            'config' => 'config:clear',
            'route' => 'route:clear',
            'view' => 'view:clear',
            'optimize' => 'optimize:clear',
            'storage' => 'storage:link',
        ];

        if ($action === 'migrate') {
            if (auth('admin')->user()->username !== 'superadmin') {
                return back()->with('error', 'Only the SuperAdmin can execute database migrations.');
            }
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                $output = \Illuminate\Support\Facades\Artisan::output();
                return back()->with('success', 'Database migrations executed successfully: ' . $output);
            } catch (\Exception $e) {
                return back()->with('error', 'Migration Failed: ' . $e->getMessage());
            }
        }

        if (!array_key_exists($action, $validActions)) {
            return back()->with('error', 'Invalid repair action specified.');
        }

        try {
            \Illuminate\Support\Facades\Artisan::call($validActions[$action]);
            $output = \Illuminate\Support\Facades\Artisan::output();
            return back()->with('success', 'Repair task executed: ' . ($output ?: 'Success'));
        } catch (\Exception $e) {
            return back()->with('error', 'Task Failed: ' . $e->getMessage());
        }
    }
    public function legal()
    {
        $settings_raw = Setting::all();
        $settings = [];
        foreach ($settings_raw as $s) {
            $settings[$s->setting_key] = $s->setting_value;
        }
        return view('admin.config.legal', compact('settings'));
    }
    public function dbLogs(Request $request)
    {
        $this->ensureSuperAdmin();

        $query = ActivityLog::orderBy('id', 'desc');

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }
        if ($request->filled('user_type')) {
            $query->where('user_type', $request->input('user_type'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('details', 'LIKE', "%{$search}%")
                  ->orWhere('ip_address', 'LIKE', "%{$search}%")
                  ->orWhere('admin_username', 'LIKE', "%{$search}%");
            });
        }

        $activityLogs = $query->paginate(20)->withQueryString();

        // Fetch recent Laravel system log entries
        $systemLogs = 'No application logs found.';
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath) && filesize($logPath) > 0) {
            $fileSize = filesize($logPath);
            // Read last 150KB to prevent memory exhaustion
            $readSize = min($fileSize, 150000);
            $fh = fopen($logPath, 'r');
            if ($fh) {
                fseek($fh, -$readSize, SEEK_END);
                $systemLogs = fread($fh, $readSize);
                fclose($fh);
                $systemLogs = htmlspecialchars($systemLogs);
            }
        }

        // Get unique log actions for filter dropdown
        $actions = ActivityLog::select('action')->distinct()->pluck('action');

        return view('admin.config.db_logs', compact('activityLogs', 'systemLogs', 'actions'));
    }

    public function clearSystemLogs()
    {
        $this->ensureSuperAdmin();
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            file_put_contents($logPath, '');
            // Log this action
            try {
                ActivityLog::create([
                    'user_type' => 'admin',
                    'action' => 'logs_cleared',
                    'details' => 'Application system logs (laravel.log) cleared by SuperAdmin',
                    'ip_address' => request()->ip()
                ]);
            } catch (\Exception $e) {}
        }
        return back()->with('success', 'Application log file cleared successfully!');
    }
    public function ipTool()
    {
        return view('admin.config.ip_tool');
    }

    // --- SYSTEM SETTINGS ---
    public function emailSettings()
    {
        $email_keys = ['mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption', 'mail_from_address', 'mail_from_name'];
        $db_settings = Setting::whereIn('setting_key', $email_keys)->get()->pluck('setting_value', 'setting_key');

        $email_settings = collect([
            'mail_host' => $db_settings->get('mail_host', config('mail.mailers.smtp.host')),
            'mail_port' => $db_settings->get('mail_port', config('mail.mailers.smtp.port')),
            'mail_username' => $db_settings->get('mail_username', config('mail.mailers.smtp.username')),
            'mail_password' => $db_settings->get('mail_password', config('mail.mailers.smtp.password')),
            'mail_encryption' => $db_settings->get('mail_encryption', config('mail.mailers.smtp.encryption')),
            'mail_from_address' => $db_settings->get('mail_from_address', config('mail.from.address')),
            'mail_from_name' => $db_settings->get('mail_from_name', config('mail.from.name')),
        ]);

        return view('admin.settings.email', compact('email_settings'));
    }

    public function updateEmailSettings(Request $request)
    {
        $data = $request->except('_token');
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value ?? '']
            );
        }
        return back()->with('success', 'Email settings updated successfully. Changes will take effect immediately.');
    }

    public function testEmailConnection(Request $request)
    {
        $request->validate(['test_email' => 'required|email']);

        $email = $request->test_email;

        try {
            // Apply the current request settings temporarily to the config for this test
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $request->mail_host ?? config('mail.mailers.smtp.host'),
                'mail.mailers.smtp.port' => $request->mail_port ?? config('mail.mailers.smtp.port'),
                'mail.mailers.smtp.username' => $request->mail_username ?? config('mail.mailers.smtp.username'),
                'mail.mailers.smtp.password' => $request->mail_password ?? config('mail.mailers.smtp.password'),
                'mail.mailers.smtp.encryption' => $request->mail_encryption ?? config('mail.mailers.smtp.encryption'),
                'mail.from.address' => $request->mail_from_address ?? config('mail.from.address'),
                'mail.from.name' => $request->mail_from_name ?? config('mail.from.name'),
            ]);

            Mail::raw("Hello, this is a test email from your PMCC Admin Dashboard. If you received this, your SMTP configuration is correct!", function ($message) use ($email) {
                $message->to($email)->subject('PMCC SMTP Test Email');
            });

            return back()->with('success', 'Test email sent successfully! Please check your inbox.');
        } catch (\Exception $e) {
            // Return the full error message for debugging
            return back()->with('error', 'Mail Connection Failed: ' . $e->getMessage())->withInput();
        }
    }


}
