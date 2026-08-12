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
                'tips' => [
                    'The amber figure on a date row is what that day costs — wages plus any extra expense logged against it.',
                    'A day with no half/whole choice for a worker follows the activity\'s own length.',
                    'Versions let you keep a plan and try another without losing the first.',
                ],
                'callout' => ['note', 'Nothing is lost', 'Deleting an activity hides it rather than destroying it — Drafts in the Tools menu brings it back.'],
            ],
            'notes' => [
                'summary' => 'The notebook for a schedule — text, photos, drawings and saved maps.',
                'intro' => "Notes are the running record of a season. Anything attached to a day or drawn by the team ends up here too, tagged with where it came from.",
                'steps_mobile' => ['Tap + to start a note.', 'Type, then add photos with the camera button.', 'Tap a note to open it; tap its pencil to edit.', 'Tap a picture to see it full screen — pinch to zoom in.'],
                'steps_desktop' => ['Click New note.', 'Write, then drag photos in or use the photo button.', 'Click a note to expand it, or its pencil to edit.', 'Click a picture to open it full screen.'],
                'tips' => ['A note tagged Team map or Team drawing came from the Collab Room.', 'A saved map opens in the Maps module rather than as a flat picture.'],
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
                'steps_mobile' => ['Tap New drawing.', 'Pick a tool, a colour and a size, then draw with your finger.', 'Use Select to move, resize or delete anything already drawn.', 'Tap the save icon and choose picture or drawing.'],
                'steps_desktop' => ['Click New drawing.', 'Pick a tool, colour and size; hold Shift for a perfect square or circle.', 'Select lets you marquee several shapes at once and move them together.', 'Save, then choose picture or drawing.'],
                'tips' => ['"Save as drawing" keeps the strokes, so it can be edited again. "Save as image" cannot.', 'A drawing saved in the Collab Room is tagged Team drawing.'],
            ],
            'weather' => [
                'summary' => 'The forecast for the ground you actually farm.',
                'intro' => "Weather is read for each lot's own location, not for the nearest city. The general tab is the week ahead; the hourly tab is today in detail.",
                'steps_mobile' => ['Swipe between the general and hourly tabs.', 'Tap a day to see what the numbers mean.'],
                'steps_desktop' => ['Switch between the general and hourly tabs.', 'Hover a day for the detail behind the summary.'],
                'tips' => ['Chance of rain is the likelihood of any rain that day, not how heavy it will be.', 'Every forecast read is kept, so a past day can be checked later from the day menu.'],
            ],
            'lots' => [
                'summary' => 'The pieces of land a schedule covers.',
                'intro' => "A lot is a named piece of ground with an area, a variety and a day-count style. Activities point at lots, and the board colours them so a day can be read at a glance.",
                'steps_mobile' => ['Tap + to add a lot.', 'Give it a name, an area and the crop variety.', 'Choose DAS or DAP so day numbers count the way you count them.'],
                'steps_desktop' => ['Click Add lot.', 'Fill in the name, area and variety.', 'Choose DAS or DAP for how its days are counted.'],
                'tips' => ['A lot with a location gets its own weather.', 'Activities can apply generally instead of to a lot — that is what N/A means on a card.'],
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
