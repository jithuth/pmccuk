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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\MemberIdCardEmail;
use Illuminate\Support\Carbon;

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
        $data = $request->all();

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
                    'age' => ($request->child_dob[$i] ?? null) ? Carbon::parse($request->child_dob[$i])->age : 0
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
                'age' => $rc->age,
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
            'dTo'
        ));
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

        $isValid = ($token === $expectedToken);
        return view('admin.members.verify', compact('member', 'isValid'));
    }

    public function sendCardEmail($id)
    {
        $member = Member::findOrFail($id);

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
        $member->update([
            'status' => 'active',
            'membership_id_assigned' => $membershipNo,
            'payment_amount' => $amount,
            'transaction_ref' => $transRef,
            'expiry_date' => $expiryDate,
            'payment_date' => Carbon::now()->format('Y-m-d')
        ]);

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
            'title' => $request->title,
            'content' => $request->content,
            'image_url' => $imageUrl,
            'status' => $request->status
        ]);

        return redirect()->route('admin.news')->with('success', 'Article published successfully!');
    }

    public function deleteNews($id)
    {
        $n = News::findOrFail($id);
        $n->delete();
        return redirect()->route('admin.news')->with('success', 'Article deleted.');
    }

    public function updateNews(Request $request, $id)
    {
        $n = News::findOrFail($id);
        $request->validate(['title' => 'required', 'content' => 'required']);

        $imageUrl = $request->input('image_url', $n->image_url);
        if ($request->hasFile('news_image')) {
            $path = $request->file('news_image')->store('news', 'public');
            $imageUrl = $path;
        }

        $n->update([
            'title' => $request->title,
            'content' => $request->content,
            'image_url' => $imageUrl,
            'status' => $request->status
        ]);

        return redirect()->route('admin.news')->with('success', 'Article updated.');
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

        $imageUrl = $request->input('image_url', $event->image_url);
        if ($request->hasFile('event_image')) {
            $path = $request->file('event_image')->store('events', 'public');
            $imageUrl = $path;
        }

        $event->update([
            'title' => $request->title,
            'description' => $request->description,
            'event_date' => $request->event_date,
            'location' => $request->location,
            'image_url' => $imageUrl,
            'rubric_id' => $request->rubric_id,
            'allow_guest_packages' => $request->has('allow_guest_packages') ? 1 : 0
        ]);

        if ($request->has('prices')) {
            EventPrice::where('event_id', $event->id)->delete();
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

        return redirect()->route('admin.events.index')->with('success', 'Event updated successfully!');
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
    public function fareLogic()
    {
        return view('admin.events.fare_logic');
    }
    public function eventStats()
    {
        return view('admin.events.stats');
    }

    // --- SPONSORS ---
    public function offers(Request $request)
    {
        $role = Auth::guard('admin')->user()->role;
        $adminId = Auth::guard('admin')->id();

        $query = SponsorOffer::withCount('redemptions');
        if ($role !== 'admin') {
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
        return view('admin.sponsors.redemptions');
    }

    // --- MEDIA & FINANCE ---
    public function gallery(Request $request)
    {
        $gallery = Gallery::orderBy('created_at', 'desc')->paginate(30);
        return view('admin.media.gallery', compact('gallery'));
    }

    public function addGallery(Request $request)
    {
        $request->validate(['gallery_image' => 'required|image']);

        if ($request->hasFile('gallery_image')) {
            $path = $request->file('gallery_image')->store('gallery', 'public');
            Gallery::create([
                'title' => $request->title,
                'image_url' => $path
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
        return view('admin.media.albums');
    }
    public function videos()
    {
        return view('admin.media.videos');
    }

    // --- SYSTEM ---
    public function securityAudit()
    {
        return view('admin.system.security');
    }
    public function accessControl(Request $request)
    {
        $this->ensureSuperAdmin();
        $admins = \App\Models\Admin::orderBy('id')->get();
        return view('admin.system.admins', compact('admins'));
    }

    public function storeAdmin(Request $request)
    {
        $this->ensureSuperAdmin();
        $request->validate([
            'username' => 'required|unique:admins,username|max:60',
            'email'    => 'nullable|email|unique:admins,email|max:120',
            'role'     => 'required|in:admin,superadmin',
            'password' => 'required|confirmed|min:8',
        ]);

        \App\Models\Admin::create([
            'username' => $request->username,
            'email'    => $request->email,
            'role'     => $request->role,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        ]);

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
        $admin = \App\Models\Admin::findOrFail($id);
        $admin->delete();
        return redirect()->route('admin.access-control')
            ->with('success', "Admin account '{$admin->username}' has been removed.");
    }

    public function updateAdminPassword(Request $request, $id)
    {
        $this->ensureSuperAdmin();
        $request->validate([
            'password' => 'required|confirmed|min:8',
        ]);

        $admin = \App\Models\Admin::findOrFail($id);
        $admin->password = \Illuminate\Support\Facades\Hash::make($request->password);
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
        $subPath = $request->get('path', '');
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
            return back()->with('success', 'File deleted.');
        }

        return back()->with('error', 'File not found or is a directory.');
    }
    public function systemRepair()
    {
        return view('admin.config.repair');
    }
    public function legal()
    {
        return view('admin.config.legal');
    }
    public function dbLogs()
    {
        $this->ensureSuperAdmin();
        return view('admin.config.db_logs');
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
