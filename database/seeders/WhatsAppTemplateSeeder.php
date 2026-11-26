<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WhatsAppTemplate;
use Illuminate\Support\Str;

class WhatsAppTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => '🌸 Onam & Vishu Cultural Celebration',
                'slug' => 'onam-vishu-greetings',
                'category' => 'greetings',
                'header_type' => 'text',
                'header_content' => '🌸 PMCC-UK ONAM & VISHU CELEBRATION 🌸',
                'body' => "Dear {name},\n\nWarmest Onam Greetings from the Plymouth Malayalee Community Club!\n\nMay this vibrant festive season bring abundant health, prosperity, and joy to you and your loved ones. Join us for a memorable day featuring the grand traditional *Onam Sadya* feast, cultural dances, Chenda Melam, and exciting community games.\n\n📍 *Venue:* {venue}\n📅 *Date:* {date}\n⏰ *Time:* {time}\n\nWe look forward to celebrating together!",
                'footer' => 'Plymouth Malayalee Community Club • Registered Community Organisation',
                'buttons' => [
                    ['type' => 'url', 'text' => 'Reserve Feast / RSVP', 'value' => 'https://pmccuk.org/events'],
                    ['type' => 'call', 'text' => 'Event Convenor', 'value' => '+447901296858']
                ],
                'is_system' => true
            ],
            [
                'name' => '🎄 Christmas & New Year Gala Night',
                'slug' => 'xmas-newyear-gala',
                'category' => 'greetings',
                'header_type' => 'text',
                'header_content' => '🎄 PMCC-UK CHRISTMAS & NEW YEAR GALA 🎅',
                'body' => "Dear {name},\n\nThe festive season has arrived! PMCC-UK warmly invites you and your family to our Annual Christmas Carol Evening and New Year Gala Dinner Dance.\n\nEnjoy an evening of live carols, special dance performances, Santa's surprise visit for kids, and an authentic gourmet dinner.\n\n📅 *Date:* {date}\n📍 *Location:* {venue}\n🎟️ *Booking Ref:* {booking_ref}\n\nDress Code: Festive Formal / Traditional.",
                'footer' => 'PMCC-UK • Sharing Joy & Fellowship',
                'buttons' => [
                    ['type' => 'url', 'text' => 'View Program Schedule', 'value' => 'https://pmccuk.org/events'],
                    ['type' => 'quick_reply', 'text' => 'Attending with Family', 'value' => 'Attending with Family']
                ],
                'is_system' => true
            ],
            [
                'name' => '🆔 Digital Membership ID Card Available',
                'slug' => 'digital-id-card-ready',
                'category' => 'membership',
                'header_type' => 'text',
                'header_content' => '🎉 YOUR DIGITAL PMCC ID CARD IS READY',
                'body' => "Dear {name},\n\nWe are delighted to confirm that your PMCC-UK Membership is active and verified!\n\n🆔 *Membership ID:* {membership_id}\n📱 *Registered Mobile:* {phone}\n📅 *Valid Through:* 31 Dec {year}\n\nYour official tamper-proof Digital ID Card has been generated. You can download and save it to your phone wallet for quick event check-in and exclusive member discounts.",
                'footer' => 'PMCC-UK Membership & Records Division',
                'buttons' => [
                    ['type' => 'url', 'text' => 'Download Digital ID Card', 'value' => 'https://pmccuk.org/member/id-card'],
                    ['type' => 'call', 'text' => 'Member Support', 'value' => '+447901296858']
                ],
                'is_system' => true
            ],
            [
                'name' => '🎟️ Event Booking & QR Entry Pass',
                'slug' => 'event-ticket-qr-pass',
                'category' => 'events',
                'header_type' => 'text',
                'header_content' => '🎟️ CONFIRMED EVENT PASS & QR CODE',
                'body' => "Hello {name},\n\nYour ticket registration for *{event_title}* has been successfully confirmed!\n\n🔖 *Booking Reference:* {booking_ref}\n📍 *Venue:* {venue}\n📅 *Date & Time:* {date} at {time}\n\n*Important Entrance Information:*\nPlease present the QR code in your booking pass at the registration desk upon arrival for rapid, contactless badge check-in.",
                'footer' => 'PMCC-UK Events Coordination Desk',
                'buttons' => [
                    ['type' => 'url', 'text' => 'View & Download Pass', 'value' => 'https://pmccuk.org/events/my-tickets'],
                    ['type' => 'quick_reply', 'text' => 'Show Venue Directions', 'value' => 'Directions']
                ],
                'is_system' => true
            ],
            [
                'name' => '⚠️ Urgent Community Safety & Weather Alert',
                'slug' => 'urgent-safety-weather-alert',
                'category' => 'alerts',
                'header_type' => 'text',
                'header_content' => '🚨 PMCC-UK URGENT NOTICE / SAFETY ADVISORY',
                'body' => "URGENT NOTICE TO ALL MEMBERS:\n\nAttention {name},\nDue to Met Office adverse weather / travel advisories in the Plymouth & South West region, please note the following updates regarding today's community schedule:\n\n⚠️ *Schedule Update:* Activities rescheduled or moved to indoor hall.\n📍 *Safe Point Venue:* {venue}\n\nPlease take extra care when traveling and check in on elderly neighbors or community members living alone.",
                'footer' => 'PMCC-UK Emergency Response & Welfare Committee',
                'buttons' => [
                    ['type' => 'url', 'text' => 'Live Situation Updates', 'value' => 'https://pmccuk.org/news'],
                    ['type' => 'call', 'text' => '24/7 Helpline', 'value' => '+447901296858']
                ],
                'is_system' => true
            ],
            [
                'name' => '🎓 Student Wing Welcome & Support Desk',
                'slug' => 'student-wing-welcome',
                'category' => 'students',
                'header_type' => 'text',
                'header_content' => '🎓 WELCOME TO PLYMOUTH, DEAR STUDENT!',
                'body' => "Hello {first_name},\n\nWelcome to Plymouth and the United Kingdom! On behalf of the entire PMCC-UK family, we are thrilled to have you here.\n\nNavigating life in a new country can feel overwhelming, but remember you are not alone! PMCC-UK provides free, friendly support for international students:\n• NHS GP Registration assistance\n• Local student accommodation guidance\n• Part-time employment & NI number help\n• Malayalam grocery & community hub pointers\n\nFeel free to join our verified Student WhatsApp network!",
                'footer' => 'PMCC-UK International Student Support Desk',
                'buttons' => [
                    ['type' => 'url', 'text' => 'Join Student WhatsApp Group', 'value' => 'https://pmccuk.org/student-corner'],
                    ['type' => 'call', 'text' => 'Student Advisor', 'value' => '+447901296858']
                ],
                'is_system' => true
            ],
            [
                'name' => '📢 Annual General Body Meeting (AGM) Notice',
                'slug' => 'agm-meeting-notice',
                'category' => 'general',
                'header_type' => 'text',
                'header_content' => '📢 NOTICE OF ANNUAL GENERAL MEETING (AGM)',
                'body' => "Dear Respected Member {name},\n\nNotice is hereby given that the Annual General Meeting (AGM) of the Plymouth Malayalee Community Club will be held as follows:\n\n📅 *Date:* {date}\n⏰ *Time:* {time} prompt\n📍 *Venue:* {venue}\n\n*Key Agenda:*\n1. President's Welcome & Annual Activity Report\n2. General Secretary's Report\n3. Financial Accounts & Audit Statements\n4. Executive Committee Elections for the upcoming term\n5. Open Q&A and Community Proposals\n\nYour presence and active participation are vital for the continued growth of our community.",
                'footer' => 'By Order of the Executive Committee, PMCC-UK',
                'buttons' => [
                    ['type' => 'url', 'text' => 'Download AGM Agenda & Accounts', 'value' => 'https://pmccuk.org/agm'],
                    ['type' => 'quick_reply', 'text' => 'Attending AGM', 'value' => 'Attending AGM']
                ],
                'is_system' => true
            ],
            [
                'name' => '💳 Annual Membership Renewal Reminder',
                'slug' => 'membership-renewal-reminder',
                'category' => 'finance',
                'header_type' => 'text',
                'header_content' => '💳 PMCC-UK ANNUAL SUBSCRIPTION RENEWAL',
                'body' => "Dear {name},\n\nThank you for your valued contribution and active membership in the Plymouth Malayalee Community Club over the past year!\n\nAs we approach our new term, this is a gentle reminder that annual membership subscriptions are now due for renewal:\n\n🆔 *Member ID:* {membership_id}\n💳 *Annual Subscription Fee:* £15.00 (Single) / £25.00 (Family)\n\nRenewing your subscription ensures uninterrupted voting rights, ticket discounts for all major cultural events, and continued access to our welfare support funds.",
                'footer' => 'PMCC-UK Finance & Membership Bureau',
                'buttons' => [
                    ['type' => 'url', 'text' => 'Renew Online Instantly', 'value' => 'https://pmccuk.org/member/renew'],
                    ['type' => 'call', 'text' => 'Treasurer Desk', 'value' => '+447901296858']
                ],
                'is_system' => true
            ],
        ];

        foreach ($templates as $data) {
            WhatsAppTemplate::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
