{{-- THE STEPS OF THE PRACTICE SEASON'S GUIDED WALK.

     Kept here rather than on the hub, because the walk CROSSES PAGES: it opens
     on the hub naming each module, then carries on over the Activities board.
     The engine looks its steps up by name on whatever page it lands on, so a
     page that does not know them stops the walk dead — which is exactly what
     happened when this list lived on the hub alone.

     Included by every page the walk visits, and only when the season being
     looked at is the practice one. --}}
@if (($schedule->isDemo ?? false))
@push('scripts')
<script>
/* THE GUIDED WALK OF THE PRACTICE SEASON.
 *
 * A step is a thing on screen and two sentences about it. The hub holds one
 * per module — which is what "shows each feature for each module" comes to at
 * this level, because the hub IS the list of modules — and then the walk
 * crosses to the Activities board, where the day-to-day work actually happens,
 * and finishes there.
 *
 * Steps whose target is missing are skipped rather than pointed at: a farm on
 * the free tier has no Collab Room tile and a worker has no Workers tile, and
 * a walk that stops dead at the first door somebody cannot open is worse than
 * one that quietly moves along.
 */
(() => {
    const board = @json(route('sm.activities', ['id' => $schedule->id], false));
    const at = (mod) => `[data-tour="${mod}"]`;

    window.aneeTour?.register('demo', [
        { title: 'This is a whole season',
          body: 'Two lots, a hired hand, and three weeks of work with some of it already done. It is real data in the real app — change it, break it, delete it. No farm depends on it.' },

        { target: at('activities'), title: 'Activities is the heart of it',
          body: 'Every job, on the day it falls. This is the screen a farm actually runs on; the rest of the modules feed it or read from it.' },

        { target: at('lots'), title: 'Lots are the ground',
          body: 'Each block of land, its crop and the day it was sown or planted. That date is what every "DAS 24" on the board is counted from.' },

        { target: at('workers'), title: 'Workers are who turns up',
          body: 'The roster, and what each person costs for half a day. Put somebody on a job and the day starts telling you what cash to bring.' },

        { target: at('inventory'), title: 'Inventory is the shed',
          body: 'What you own and what has moved. Spending on an activity comes out of here, so the stock and the plan cannot drift apart.' },

        { target: at('notes'), title: 'Notes are what you saw',
          body: 'Words, photos, a clip, a voice memo. Notes keep working with no signal, which is the point — the field is where you notice things.' },

        { target: at('weather'), title: 'Weather sits on the days',
          body: 'The forecast lands on each day of the board, so a spray planned into the rain is obvious before anyone drives out.' },

        { target: at('growth'), title: 'Growth stages read the crop',
          body: 'What the plant is doing at its current day count, and what it wants. Day 21 of rice is active tillering, and the app says so rather than making you remember.' },

        { target: at('reports'), title: 'Reports add it all up',
          body: 'Labour, expenses and profit, computed from the same rows you have been editing. Nothing here is typed twice.' },

        { target: at('gallery'), title: 'The Gallery keeps the pictures',
          body: 'Every photo from every day, in albums. A drawing or a map tagged to a note opens the note it belongs to.' },

        { target: at('ai'), title: 'Anee answers questions',
          body: 'Ask about this season and she reads it before she replies — the lots, the dates, the work. Answers cost credits; a new account starts with some.' },

        // And over to the board, where the rest of the walk happens.
        { goTo: board, target: '#activitiesList .date-group', title: 'A day at a time',
          body: 'The board groups work by the day it falls on. Tap a date to fold it away; the count and the money on the header tell you what is in there without opening it.' },

        { goTo: board, target: '#activitiesList .activity-card', title: 'One job, one card',
          body: 'The lot and its day count, the priority, who is on it and how long it takes. Tick the box when it is done. Drag the card to move it to another day.' },

        { goTo: board, target: '#activitiesList .date-header-cash', title: 'What a day costs',
          body: 'Wages for everyone on that day plus any expense logged against it. Tap it for the longhand, or to total a stretch of days from here to another.' },

        { goTo: board, target: '#mirrorBtn', title: 'Mirror view reads the whole plan',
          body: 'The season held up to be read: every day, every job, nothing to tap by accident. It is the view for checking, not working.' },

        { goTo: board, target: '#actToolbar', title: 'And that is the tour',
          body: 'Everything else lives in this bar — search, what the board shows, the tools. Open your own season when you are ready; this one stays until you delete it.' },
    ]);

    document.getElementById('demoTourBtn')?.addEventListener('click', () => window.aneeTour?.start('demo'));
})();
</script>
@endpush
@endif
