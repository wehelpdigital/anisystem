<?php

namespace Database\Seeders;

use App\Models\AsCommunityBlogPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Sample Technician's Blog articles so the Blog tab isn't empty in demos.
 * Idempotent — keyed on the slug, re-running won't duplicate.
 */
class CommunityBlogSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $posts = [
            [
                'title' => 'Managing water in the first 21 days of rice',
                'author' => 'Eng. Dela Cruz',
                'excerpt' => 'Keep it shallow and steady — how early water depth sets up your whole season.',
                'body' => '<p>The first three weeks decide a lot. Keep the paddy at <strong>2–3 cm</strong> right after transplanting so the seedlings anchor without drowning.</p>'
                    . '<h3>Week by week</h3><ul><li><strong>Days 1–7:</strong> saturated to 2 cm. Level fields drain evenly and starve early weeds.</li>'
                    . '<li><strong>Days 8–14:</strong> hold 3 cm. This is when tillering starts — steady water means more productive tillers.</li>'
                    . '<li><strong>Days 15–21:</strong> keep 3–5 cm, top up after heavy sun.</li></ul>'
                    . '<p>Log your top-ups as a per-day note so next season you can see exactly when the field ran dry.</p>',
            ],
            [
                'title' => 'Reading your labour report before you spend',
                'author' => 'AniSenso Team',
                'excerpt' => 'Your worker-days are money. Here is how to spot an expensive week early.',
                'body' => '<p>Open <strong>Reports → Labour Report</strong> and look at the per-phase totals. If land-prep labour is climbing past a third of your season budget, something is off — usually too many whole-day tasks stacked on the same dates.</p>'
                    . '<h3>Three quick checks</h3><ol><li>Spread heavy tasks across days instead of piling them on one.</li>'
                    . '<li>Mark rest days so the report does not count idle time.</li>'
                    . '<li>Compare the labour total against your Post-Harvest revenue report — aim to keep labour under what the yield can carry.</li></ol>',
            ],
            [
                'title' => 'When to spray, and when to wait',
                'author' => 'Eng. Ramos',
                'excerpt' => 'Chasing every pest costs more than it saves. Scout first, spray with a reason.',
                'body' => '<p>Walk the field before you mix anything. Check five spots, ten hills each. If you are under the action threshold, waiting a few days often lets natural predators do the work for free.</p>'
                    . '<blockquote>A spray you did not need is money burned twice — the chemical, and the beneficial insects you killed.</blockquote>'
                    . '<p>Record every spray as an activity with the material used. Over a season the pattern tells you which fields are worth pre-emptive protection and which are not.</p>',
            ],
            [
                'title' => 'Post-harvest: turning a good season into next season\'s plan',
                'author' => 'AniSenso Team',
                'excerpt' => 'Yield, moisture, buyer, price — capture it now while the numbers are fresh.',
                'body' => '<p>The moment the harvest is weighed, open <strong>Reports → Post-Harvest Report</strong> and enter your yield and the price you got. The app subtracts materials, services, labour and your extra expenses so you see the real net — not just the gross.</p>'
                    . '<p>Save it as a copy. Next season you plan from real figures instead of memory, and you can compare lots side by side to see which ones actually paid.</p>',
            ],
            [
                'title' => 'Choosing the right rice variety for your field',
                'author' => 'Eng. Dela Cruz',
                'excerpt' => 'Match the variety to your water, season, and market — not just the yield claim.',
                'body' => '<p>The highest-yielding variety on paper is not always the best for your field. Start with three questions: how reliable is your water, how long is your growing window, and who buys your palay?</p>'
                    . '<h3>What to weigh</h3><ul><li><strong>Maturity:</strong> short-duration varieties dodge late-season typhoons.</li><li><strong>Pest resistance:</strong> tungro or BPH pressure in your area should narrow the list fast.</li><li><strong>Grain quality:</strong> your buyer\'s preference sets your price more than a few extra cavans.</li></ul>',
            ],
            [
                'title' => 'Building healthy soil between seasons',
                'author' => 'AniSenso Team',
                'excerpt' => 'The fallow is not downtime — it is when next season\'s yield is decided.',
                'body' => '<p>What you do between crops matters as much as what you do during them. Incorporate rice straw instead of burning it, and you return organic matter and potassium to the soil for free.</p>'
                    . '<p>Consider a short mungbean cover crop: it fixes nitrogen, smothers weeds, and gives you a small harvest before the next main crop.</p>',
            ],
            [
                'title' => 'Integrated pest management, the practical way',
                'author' => 'Eng. Ramos',
                'excerpt' => 'Scout, threshold, then act — in that order, every time.',
                'body' => '<p>IPM sounds academic but it is just discipline. <strong>Scout</strong> your field twice a week. <strong>Check the threshold</strong> before reaching for a sprayer. <strong>Act</strong> with the least disruptive tool first.</p>'
                    . '<blockquote>Beneficial insects are free labour. Every needless spray fires them.</blockquote>'
                    . '<p>Log each scouting trip so you can see pressure building before it becomes a problem.</p>',
            ],
            [
                'title' => 'Fertilizer timing: split, don\'t dump',
                'author' => 'Eng. Dela Cruz',
                'excerpt' => 'Three smaller doses beat one big one — for your yield and your budget.',
                'body' => '<p>Dumping all your nitrogen early feeds weeds and washes away in the rain. Split it: a basal dose, one at active tillering, and one at panicle initiation.</p>'
                    . '<p>Use a leaf colour chart to fine-tune the last dose — it tells you what the crop actually needs instead of guessing.</p>',
            ],
            [
                'title' => 'Water management for higher yields',
                'author' => 'AniSenso Team',
                'excerpt' => 'Alternate wetting and drying saves water and often lifts yield.',
                'body' => '<p>Keeping the paddy flooded the whole season wastes water and can starve roots of oxygen. Try <strong>alternate wetting and drying</strong>: let the water drop to 15cm below the surface, then re-flood.</p>'
                    . '<p>Skip AWD during flowering — that stage needs steady water. Everywhere else, your pump bill drops and the crop rarely complains.</p>',
            ],
            [
                'title' => 'Cutting harvest and post-harvest losses',
                'author' => 'Eng. Ramos',
                'excerpt' => 'You can lose a tenth of your crop after it is grown. Here is where it goes.',
                'body' => '<p>Losses hide in the last mile: shattering from late harvest, grains left in the field, and moulds from poor drying. Harvest at the right moisture and thresh promptly.</p>'
                    . '<p>Dry to 14% moisture for storage. A cheap moisture meter pays for itself the first season it saves a batch from spoiling.</p>',
            ],
            [
                'title' => 'Selling smarter: reading the palay market',
                'author' => 'AniSenso Team',
                'excerpt' => 'Timing and quality move your price more than yield does.',
                'body' => '<p>Everyone harvests at once, so prices dip at peak. If you can dry and store cleanly, holding a few weeks often pays. If you cannot, sell dry and clean — buyers dock wet, dirty grain hard.</p>'
                    . '<p>Track the price you actually got each season in your Post-Harvest reports; patterns appear after a year or two.</p>',
            ],
            [
                'title' => 'Financing your season without drowning in debt',
                'author' => 'Eng. Dela Cruz',
                'excerpt' => 'Know your cost per hectare before you borrow a peso.',
                'body' => '<p>The safest loan is the one you sized correctly. Add up seeds, fertiliser, labour, and machine rental per hectare first — the app\'s cost breakdown does this for you.</p>'
                    . '<p>Borrow against a realistic yield and a conservative price, never the best case. A season that only breaks even is survivable; one that leaves you underwater is not.</p>',
            ],
            [
                'title' => 'Mechanisation: rent before you buy',
                'author' => 'Eng. Ramos',
                'excerpt' => 'A machine that sits idle 10 months a year is a liability, not an asset.',
                'body' => '<p>Transplanters and combines are transformative — and expensive. Before buying, rent for a season or two and track the real cost per hectare against your own labour.</p>'
                    . '<p>Often a shared or rented machine at peak weeks beats owning one that depreciates in the shed. Run the numbers, not the pride.</p>',
            ],
            [
                'title' => 'Climate-smart farming for the wet season',
                'author' => 'AniSenso Team',
                'excerpt' => 'Plan for the typhoon you hope never comes.',
                'body' => '<p>Wet-season farming is risk management. Favour shorter-duration, lodging-resistant varieties, and stagger planting so one storm cannot flatten everything at once.</p>'
                    . '<p>Keep drainage canals clear before the rains, and mark a resume-here point in your schedule so you can pick up cleanly after a disruption.</p>',
            ],
        ];

        foreach ($posts as $p) {
            $slug = Str::slug($p['title']);
            AsCommunityBlogPost::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $p['title'],
                    'authorName' => $p['author'],
                    'excerpt' => $p['excerpt'],
                    'body' => $p['body'],
                    'isPublished' => 1,
                    'publishedAt' => $now,
                    'deleteStatus' => 1,
                ]
            );
        }
    }
}
