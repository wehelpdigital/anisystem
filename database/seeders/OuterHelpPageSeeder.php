<?php

namespace Database\Seeders;

use App\Models\AsTutorialPage;
use Illuminate\Database\Seeder;

/**
 * A first page behind every question mark outside the cropping schedule.
 *
 * Written so the icon is never a dead end. These are starting points, not
 * final copy — the builder in the mother app edits every one of them, and a
 * page written there replaces what is here. Only pages that do not exist yet
 * are created, so this never overwrites somebody's editing.
 *
 * One page per screen, filed as `mobile`: the reader is almost always on a
 * phone, and the help controller serves the mobile page to a desktop before
 * it serves nothing.
 */
class OuterHelpPageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->book() as $key => [$title, $summary, $blocks]) {
            $exists = AsTutorialPage::where('moduleKey', $key)->exists();
            if ($exists) {
                continue;
            }
            AsTutorialPage::create([
                'moduleKey' => $key,
                'device' => 'mobile',
                'title' => $title,
                'summary' => $summary,
                'blocks' => $blocks,
                'deleteStatus' => 1,
            ]);
        }

        $this->command?->info('Help pages: the screens outside the schedule have one.');
    }

    private function t(string $text): array
    {
        return ['kind' => 'text', 'text' => $text];
    }

    private function h(string $text): array
    {
        return ['kind' => 'heading', 'text' => $text];
    }

    private function steps(array $items): array
    {
        return ['kind' => 'steps', 'items' => $items];
    }

    private function tips(array $items): array
    {
        return ['kind' => 'tips', 'items' => $items];
    }

    private function callout(string $text): array
    {
        return ['kind' => 'callout', 'text' => $text];
    }

    /** @return array<string, array{0:string,1:string,2:array}> */
    private function book(): array
    {
        return [
            'home' => ['How to use Home', 'The one screen that answers "what about today?"', [
                $this->t("Home is the answer to one question: what does today need from me?\n\nEverything on it is today's — the weather where your farm is, the activities on your boards, and how far through the season each lot has got."),
                $this->h('What is on it'),
                $this->tips([
                    'The greeting reads the forecast. On a wet morning it says so, and the picture beside it is the sky you are about to walk out into.',
                    'The forecast panel is per farm location. Each one carries a line about what that weather means for the work — hold the spraying, get the drying under cover.',
                    'Your cropping schedules are underneath, each showing where its lots stand today and how far through the season they are.',
                    'The quick tools file a photo or a recording without opening a schedule first.',
                ]),
                $this->h('Set your location'),
                $this->steps([
                    'Open a cropping schedule, then Settings.',
                    'Set the farm location. The forecast follows that, not your phone.',
                    'A schedule with more than one location gets a forecast for each.',
                ]),
                $this->callout('No forecast showing? The schedule has no location set yet. Everything else on Home works without one.'),
            ]],

            'account' => ['How to use Account', 'Who you are in the app, and everything that is yours.', [
                $this->t('Account holds the things that are yours rather than a season\'s: your name and photo, how the app looks and behaves, your subscription, and the way out.'),
                $this->h('What you can change'),
                $this->tips([
                    'Your name, photo and cover — these are what the community sees.',
                    'Light or dark, text size, and reduced motion. The app remembers per device.',
                    'Your password and how you sign in.',
                    'Your subscription: what plan you are on and what it allows.',
                ]),
                $this->callout('Changing your photo here changes it everywhere — your posts, your comments, the collab room and the team lists.'),
            ]],

            'community' => ['How to use Community', 'The feed: what other farmers are posting, and what you post back.', [
                $this->t("The feed is the front page of Community. Posts from the farmers you follow, the discussions you are in, and what the app thinks you would want to see.\n\nEverything else in Community is reachable from here."),
                $this->h('Posting'),
                $this->steps([
                    'Tap the composer at the top.',
                    'Write, then add pictures or a video — from the camera, from this phone, or from your own gallery.',
                    'Post. You can edit or delete it afterwards from the ⋯ menu on your own post.',
                ]),
                $this->h('Reading'),
                $this->tips([
                    'React, comment, share, or bookmark a post to find it again in Saved.',
                    'Tap a name or a photo to open that farmer\'s profile.',
                    'A hashtag opens everything filed under it.',
                ]),
            ]],

            'community-discussions' => ['How to use Discussions', 'Rooms about one thing, with their own doors.', [
                $this->t("A discussion is a room about one subject — a crop, a province, a problem. It has its own posts, its own chat, its own members and its own door."),
                $this->h('Starting one'),
                $this->steps([
                    'Tap New discussion.',
                    'Give it a name, a badge picture and a cover photo. Each picture can come from the camera, from this phone, or from your gallery.',
                    'Choose who gets in: open to anybody, a password you pass on, or you approve each one.',
                    'Create. You are its first member and its organiser.',
                ]),
                $this->h('Inside a room'),
                $this->tips([
                    'Posts work as they do in the feed, but stay in the room.',
                    'Chat is live and separate from the posts.',
                    'Members lists who is in. An organiser can remove somebody and say why.',
                    'Edit changes the name, the description and both pictures.',
                ]),
                $this->callout('A closed room is still findable — its name, cover and size show — but nothing said inside it does.'),
            ]],

            'community-members' => ['How to use Members', 'Every farmer on AniSenso, and how to reach them.', [
                $this->t('Members is the whole list. Search it, follow somebody, or send a request to become co-farmers.'),
                $this->tips([
                    'Follow is one-way: you see their posts, they need not see yours.',
                    'Co-farmers is both ways, and has to be accepted. Requests waiting for you are at the top.',
                    'Tap anybody to open their profile — what they grow, where, and what they have posted.',
                ]),
            ]],

            'community-cofarmers' => ['How to use Co-farmers', 'The farmers you have agreed to keep up with.', [
                $this->t('Co-farmers are mutual: both of you agreed. This page is their latest, gathered away from the noise of the whole feed.'),
                $this->steps([
                    'Find somebody in Members or from a post.',
                    'Send a co-farmer request.',
                    'When they accept, they appear here and you appear on theirs.',
                ]),
            ]],

            'community-blog' => ['How to use the Blog', 'Longer writing — guides, seasons, what worked.', [
                $this->t('The blog is for the things a feed post is too short for: a whole season written up, a method explained properly, a warning worth the detail.'),
                $this->tips([
                    'Anybody can read. Writing is a longer editor with headings and pictures.',
                    'A blog post can be shared into the feed or into a discussion.',
                ]),
            ]],

            'community-ranking' => ['How to use Rankings', 'What the ladder counts, and how to climb it.', [
                $this->t("Rank is earned by using the app and helping other people use it: farming your own seasons, posting, answering, and turning up.\n\nIt is not a competition anybody loses. Everything that raises it is something worth doing anyway."),
                $this->tips([
                    'Points come from activity across the whole app, not from posting alone.',
                    'Your badge shows beside your name wherever you appear.',
                    'The diary shows which days you were counted.',
                ]),
            ]],

            'community-saved' => ['How to use Saved', 'Everything you bookmarked, in one place.', [
                $this->t('Bookmark anything worth coming back to — a method, a price, a warning — and it lands here. Nobody is told you saved it.'),
                $this->steps([
                    'Tap the bookmark on any post.',
                    'Come here to find it again.',
                    'Tap the bookmark a second time to take it off the list.',
                ]),
            ]],

            'community-messages' => ['How to use Messages', 'Talking to one farmer, or a few.', [
                $this->t('Messages are private — not on the feed, not in a room. Pictures, videos and voice notes go in the same way they do everywhere else in the app.'),
                $this->tips([
                    'Start one from a profile, or from the messages icon.',
                    'Unread counts show on the icon and beside the thread.',
                    'A message can carry a photo from your gallery as well as from the camera.',
                ]),
            ]],

            'community-profile' => ['How to use your profile', 'What other farmers see when they tap your name.', [
                $this->t('Your profile is your face in Community: your photo, your cover, what you grow, where you farm, and everything you have posted.'),
                $this->tips([
                    'The headline under your name is yours to write — say what you grow and where.',
                    'Your rank badge and your co-farmer count sit alongside it.',
                    'What you post is public unless it was posted inside a closed discussion.',
                ]),
                $this->callout('Change the photo, the cover and the headline from Account. They apply everywhere at once.'),
            ]],

            'inventory' => ['How to use Inventory', 'What you have, what went out, and what it cost.', [
                $this->t("Inventory is items and movements. The amount on hand is not typed in — it is the sum of everything that has moved in and out, so it cannot drift away from the truth.\n\nSpending an item on an activity is recorded once and can be taken back."),
                $this->steps([
                    'Add an item: what it is, the unit it is counted in, and what it costs.',
                    'Record a movement in when it arrives.',
                    'Attach it to an activity to spend it. The activity carries the cost and the stock comes down.',
                ]),
            ]],

            'media' => ['How to use Media', 'Photos, videos and recordings from a season.', [
                $this->t('Media gathers what a season has been photographed, filmed and recorded doing. Anything filed from an activity, a note, a drawing or a map is here too.'),
                $this->tips([
                    'Albums group pictures; the All view shows everything newest first.',
                    'A picture opened from a note takes you back to that note.',
                    'Anything captured with the quick tools lands here.',
                ]),
            ]],
        ];
    }
}
