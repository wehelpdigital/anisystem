<?php

namespace Database\Seeders;

use App\Models\AsTutorialPage;
use Illuminate\Database\Seeder;

/**
 * Starting content for every "How to use" page.
 *
 * Written to be rewritten: the mother app's builder edits these blocks, so
 * what matters here is that each module has something true and specific to
 * this app on day one, in the shape the builder understands.
 *
 * Per device, only what actually differs is said differently. A phone reaches
 * things through the kebab and sheets that slide up; a desktop has a toolbar
 * row that phones never see. Everything else is the same work.
 */
class TutorialPageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $moduleKey => $spec) {
            foreach (AsTutorialPage::DEVICES as $device) {
                AsTutorialPage::updateOrCreate(
                    ['moduleKey' => $moduleKey, 'device' => $device],
                    [
                        'title' => 'How to use ' . AsTutorialPage::label($moduleKey),
                        'summary' => $spec['summary'],
                        'blocks' => $this->blocks($spec, $device),
                        'deleteStatus' => 1,
                    ]
                );
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function blocks(array $spec, string $device): array
    {
        $phone = $device === 'mobile';
        $blocks = [
            ['kind' => 'text', 'text' => $spec['intro']],
            ['kind' => 'heading', 'text' => 'Getting started'],
            ['kind' => 'steps', 'items' => $phone ? $spec['steps_mobile'] : $spec['steps_desktop']],
        ];

        if (! empty($spec['tips'])) {
            $blocks[] = ['kind' => 'heading', 'text' => 'Worth knowing'];
            $blocks[] = ['kind' => 'tips', 'items' => $spec['tips']];
        }

        if (! empty($spec['callout'])) {
            $blocks[] = [
                'kind' => 'callout',
                'tone' => $spec['callout'][0],
                'title' => $spec['callout'][1],
                'text' => $spec['callout'][2],
            ];
        }

        if ($device === 'tablet') {
            $blocks[] = [
                'kind' => 'callout', 'tone' => 'note', 'title' => 'On a tablet',
                'text' => 'A tablet in landscape gets the desktop toolbar; held upright it behaves like a phone, '
                    . 'so both sets of instructions above apply depending on how you are holding it.',
            ];
        }

        return $blocks;
    }

    /** @return array<string, array<string, mixed>> */
    private function pages(): array
    {
        return [
            'activities' => [
                'summary' => 'The day-by-day board: what happens, when, who does it and what it costs.',
                'intro' => "The board is one row per day. A day opens to show its activities, and every activity carries its lots, its workers, its materials and its notes.\n\nNothing is saved to a separate place — a drawing, a map or a note you attach to a day stays with that day.",
                'steps_mobile' => [
                    'Tap a date row to open it. Tap it again to fold it away.',
                    'Tap + on a date to add an activity to that day.',
                    'In the activity sheet, pick Task, Irrigation or Service, then Workers to say who is on it.',
                    'Tap the 3 dots on a date for that whole day: add a note, a drawing, a map, income or an extra expense.',
                    'Tap the eye button to hide empty dates or days where everything is done.',
                ],
                'steps_desktop' => [
                    'Click a date row to open it, or use Contract All in the Tools menu to fold everything.',
                    'Click + on a date to add an activity, or Add Activity for a new one anywhere.',
                    'Pick Task, Irrigation or Service, then the Workers tab to set half or whole days and any agreed rate.',
                    'The icons on each date row cover the whole day: note, expense, marker, share, move and delete.',
                    'Drag an activity by its grip to reorder it, or drag a date header to move the whole day.',
                ],
                'tips' => ['Advanced info, on a card\'s ⋮ menu, says how many days before this task those lots last had a herbicide, a foliar spray, granular fertiliser, a pesticide, a fungicide, copper or a biological.', 'A task can carry more than one type: tap every tag that goes in the same tank. The first one tapped leads — it colours the card and answers the filters.', 'The amber pill on a day header is worth reading: a spray before rain, two jobs on one lot, or copper sharing a tank, which it should never do.', 'Tools → Growth stage reads every lot as it stands today.'],
                'callout' => ['note', 'Nothing is lost', 'Deleting an activity hides it rather than destroying it — Drafts in the Tools menu brings it back.'],
            ],
            'notes' => [
                'summary' => 'The notebook for a schedule — words first, with everything else attached.',
                'intro' => "Notes are the running record of a season. Anything attached to a day or drawn by the team ends up here too, tagged with where it came from.",
                'steps_mobile' => ['Tap + to start a note and give it a title.', 'The row of tools attaches things: take a photo, upload one, attach or record a video, add an emoji.', 'Tap a note to open it; tap its pencil to edit.', 'Tap an attachment tag to open what it points at.'],
                'steps_desktop' => ['Click New note, give it a title and write.', 'Attach photos, videos, a drawing or a map with the tools above the text.', 'Click a note to expand it, or its pencil to edit.', 'Click an attachment tag to open it where it lives.'],
                'tips' => ['Attachments show as tags rather than a wall of thumbnails — a photo opens full screen, a drawing opens the pad that can change it, a map opens Maps.', 'A drawing or map made inside a note also lives in the Drawings and Maps modules. One record, seen from two places.', 'Global notes gathers every note from every schedule, day notes included.'],
            ],
            'maps' => [
                'summary' => 'Draw the ground: lines, shapes, distances and areas on a real map.',
                'intro' => "Trace what is actually in the field — a boundary, a channel, a path — and the map measures it for you.",
                'steps_mobile' => ['Tap the tool button and pick a tool.', 'Tap points on the map to place them; the distance shows as you go.', 'Press and hold a point to carry on drawing from it; tap the first point to close a shape and get its area.', 'Drag a pin to move it — the lines follow.', 'Tap Save to keep the map, or save it as a picture in your notes.'],
                'steps_desktop' => ['Pick a tool from the toolbar.', 'Click to place points; the running distance shows as you draw.', 'Click the first point to close a shape and read its area.', 'Use Select to move a point, recolour a shape or delete one.', 'Save keeps the map so it can be reopened and changed later.'],
                'tips' => ['Measurements are shown to two decimal places and never rounded up.', 'Undo and redo are in the toolbar, and Ctrl+Z works too.'],
            ],
            'draw' => [
                'summary' => 'A blank pad for sketching what a map cannot show.',
                'intro' => "Freehand drawing with shapes, arrows and text. Keep it as a flat picture, or as a drawing you can reopen and change.",
                'steps_mobile' => ['Tap New drawing.', 'Pick a tool, colour and size.', 'Save, then choose picture or drawing.', 'Tap a card to open it again.'],
                'steps_desktop' => ['Click New drawing.', 'Pick a tool, colour and size; hold Shift for a perfect square or circle.', 'Select lets you marquee several shapes and move them together.', 'Save, then choose picture or drawing.'],
                'tips' => ['"Save as drawing" keeps the strokes, so it can be edited again. "Save as image" cannot.', 'A drawing saved in the Collab Room is tagged Team drawing, and drawing over it starts a new one rather than rewriting the team\'s.', 'Every drawing lives on a note — the "In a note" tag opens the words that explain it.', 'Open a drawing from a note and closing the pad takes you back to that note.'],
            ],
            'growth' => [
                'summary' => 'What each lot is doing today, and what it needs.',
                'intro' => "The board counts days — DAS from sowing, DAT from transplanting, DAP from planting. This page turns that number into what the plant is actually doing, and what the work of the week is.",
                'steps_mobile' => ['Set the crop on each lot in the Lots module.', 'Give the lot a day zero, or tick "this is day zero" on the activity that starts the count.', 'Open Growth Stages from the menu or the hub.', 'Change the date at the top to read the crop on any day — next week, or last month.'],
                'steps_desktop' => ["Set each lot's crop in Lots and give it a day zero.", 'Open Growth Stages from the hub.', 'Each lot shows its stage, what to do now, what to watch for, and where it sits in the season.', 'Change the date to plan ahead.'],
                'tips' => ['Rice is read from transplanting where a lot has a transplant date, and from sowing where it does not — the label says which.', 'The day header in Activities has the same reading for that day, on the plant pill beside the cost.'],
            ],
            'gallery' => [
                'summary' => 'Every picture the season made, and the albums you put together yourself.',
                'intro' => "Two questions, two tabs. All pictures is everything this schedule has produced anywhere — a photo in a note, a picture on a day, a drawing, a saved map, a frame you sent the AI. Albums are the ones you grouped and named on purpose.\n\nNothing here is a copy: delete a photo where it lives and it leaves the Gallery too.",
                'steps_mobile' => ['Open Gallery from the menu or the hub.', 'All pictures is everything, newest first; the chips narrow it to notes, drawings, maps and the rest.', 'Tap a photo to open it full screen; tap a drawing or a map to open it where it can be changed.', 'Switch to Albums, tap New album, then + to add pictures to it.'],
                'steps_desktop' => ['Open Gallery from the hub or the modules menu.', 'Search All pictures by what a picture was about, or filter by where it came from.', 'Switch to Albums to group pictures on purpose — New album, then + to fill it.', 'Tick pictures to move them to another album or remove them.'],
                'tips' => ['Quick Capture can send photos straight into an album — choose Save to gallery.', 'Deleting an album asks what to do with its pictures rather than taking them with it.', 'This is where the Media Box went. One place for pictures instead of two.'],
            ],
            'weather' => [
                'summary' => 'The forecast for the ground you actually farm.',
                'intro' => "Weather is read for each lot's own location, not for the nearest city. One card per place, with the lots it covers named on it.",
                'steps_mobile' => ['The line at the top says today in words — how warm, and how likely rain is.', 'The strip below is the next six days.', 'Tap a day to open its hours underneath: when rain starts, the wettest hour, and whether there is a dry window.', 'Tap the same day again to close it.'],
                'steps_desktop' => ['Each card covers one location and names the lots it applies to.', 'The six-day strip carries the symbol, the high and low, and the chance of rain.', 'Click a day to open that day hour by hour.', 'Click it again to close it.'],
                'tips' => ['Chance of rain is the likelihood of any rain, not how heavy it will be.', 'A lot with no town and province cannot be forecast — set them in Lots.', 'Every forecast read is kept, so a past day can be checked later from the day menu.'],
            ],
            'lots' => [
                'summary' => 'The pieces of land a schedule covers.',
                'intro' => "A lot is a named piece of ground with an area, a crop and a day zero. Activities point at lots, and everything that counts days reads them from here.",
                'steps_mobile' => ['Tap + to add a lot, then give it a name and an area.', 'Tap the crop that grows there — tap it again to clear it.', 'Choose the day counter, and set day zero (or tick "this is day zero" on the activity that starts the count).', 'Fill in the town and province so the lot gets its own weather.'],
                'steps_desktop' => ['Click Add lot and fill in the name, area and variety.', 'Tap the crop chip that matches what grows there.', 'Choose the day counter and set day zero.', 'Give it a town and province for weather.'],
                'tips' => ['DAS → DAT — sown, then transplanted: counts DAS from day zero, restarts as DAT on the transplant date, and reads the transplanted calendar from then on.', 'DAS only — direct seeded (DSR): one count from sowing, read against the direct-seeded calendar. A transplant date is ignored.', 'DAP — days after planting: one count, for anything planted rather than sown.', 'It is per lot: the same rice can be transplanted on one block and direct seeded on the next.'],
            ],
            'workers' => [
                'summary' => 'Who works the schedule, and what they are paid.',
                'intro' => "Each worker carries a half-day rate. That is what the board uses to work out what a day costs, unless an activity agrees something different.",
                'steps_mobile' => ['Tap + to add a worker.', 'Enter their name and half-day rate.', 'Assign them to activities from the activity sheet.'],
                'steps_desktop' => ['Click Add worker.', 'Enter the name and half-day rate; contact details are optional.', 'Assign them when you add or edit an activity.'],
                'tips' => ['A whole day is two half-days at their rate unless an amount is agreed on the activity.', 'Giving a worker a login lets them see the schedule without being able to change it.'],
            ],
            'documentation' => [
                'summary' => 'Files that belong to the whole schedule.',
                'intro' => 'Permits, receipts, lab results — anything that is about the season rather than about one day.',
                'steps_mobile' => ['Tap + to upload.', 'Give the file a name you will recognise later.'],
                'steps_desktop' => ['Click Upload, or drag files onto the page.', 'Name each file so it can be found later.'],
            ],
            'post-harvest' => [
                'summary' => 'What came off the field, and what it sold for.',
                'intro' => 'Record yields and sales here; the revenue report reads from them.',
                'steps_mobile' => ['Tap + to add a harvest record.', 'Enter the weight, the buyer and the price.'],
                'steps_desktop' => ['Click Add record.', 'Enter weight, buyer and price; the revenue report picks it up.'],
            ],
            'reports' => [
                'summary' => 'What the season cost and what it returned.',
                'intro' => 'Reports are built from what is already on the board — labour from the activities, expenses from the days, revenue from post-harvest.',
                'steps_mobile' => ['Tap a report to open it.', 'Use the date range to narrow it.'],
                'steps_desktop' => ['Choose a report.', 'Set the date range, then export if you need it elsewhere.'],
                'callout' => ['note', 'Where the numbers come from', 'A report never holds its own figures. Correct the day and the report corrects itself.'],
            ],
            'settings' => [
                'summary' => 'How this schedule behaves.',
                'intro' => 'Names, dates, rest days and who gets told what.',
                'steps_mobile' => ['Edit the basics, then Save.', 'Open the Notifications tab to set the daily schedule email.'],
                'steps_desktop' => ['Edit the basics and save.', 'The Notifications tab controls the daily schedule email for you and your workers.'],
                'tips' => ['Marking a schedule completed locks it — reopen it here to make changes again.'],
            ],
            'collab' => [
                'summary' => 'The room where the team works at the same time.',
                'intro' => "Chat, a shared whiteboard, the map and the activities board, all live for everyone who is in the room.",
                'steps_mobile' => ['Tap a tab to switch between chat, board, map and activities.', 'Tap the call button to talk; leaving the call leaves it running for everyone else.'],
                'steps_desktop' => ['Switch tabs for chat, whiteboard, map and activities.', 'Start a call from the toolbar; others can join and leave freely.'],
                'tips' => ['Undo on the whiteboard takes back your own last stroke, never a teammate\'s.', 'Saving the board files it in the notebook as a Team drawing.'],
            ],
            'ai' => [
                'summary' => 'A technician that has read your schedule.',
                'intro' => 'Ask about the plan, the weather, or what to do next. It answers with what is actually on this schedule.',
                'steps_mobile' => ['Type a question and send it.', 'Tap a suggestion to ask it as-is.'],
                'steps_desktop' => ['Type a question, or pick one of the suggestions.', 'Answers reference the days and lots they came from.'],
            ],
            'hub' => [
                'summary' => 'Everything one schedule contains, in one place.',
                'intro' => 'The hub is the way into every module for this schedule, with a count on each so you can see what is filled in.',
                'steps_mobile' => ['Tap a tile to open that module.', 'The readiness list shows what still needs attention.'],
                'steps_desktop' => ['Click a tile to open a module.', 'The readiness list links straight to whatever is missing.'],
            ],
            'schedules' => [
                'summary' => 'Every season you are running.',
                'intro' => 'Each card is one cropping schedule. Open one to work on it, or start another.',
                'steps_mobile' => ['Tap Open on a schedule.', 'Use + to start a new one.', 'Global notes gathers every note from every schedule.'],
                'steps_desktop' => ['Click Open on a schedule.', 'New Cropping Schedule starts another.', 'Global notes gathers every note from every schedule.'],
                'tips' => ['Duplicating a schedule copies its plan without its records — useful for next season.'],
            ],
        ];
    }
}
