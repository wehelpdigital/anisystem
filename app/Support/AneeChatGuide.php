<?php

namespace App\Support;

/**
 * "How to chat with Anee" — the full guide behind "Check this for a complete
 * guide" on the how-to-ask card in every chat (the chat page, the floating
 * chat, the schedule chat, the Collab Room). 2026-09-29, the owner's ask.
 *
 * The page itself is a How-to Guide like any module's (as_tutorial_pages,
 * moduleKey "anee-chat"), so the mother app's block builder edits it. What is
 * here is the starting text: the migration seeded it as the phone page, and it
 * is what shows if every written page is ever removed.
 */
class AneeChatGuide
{
    public const MODULE = 'anee-chat';

    public const TITLE = 'How to chat with Anee';

    public const SUMMARY = 'Ask well and you get a better answer the first time, for fewer credits.';

    /** Blocks in the TutorialBlocks vocabulary. */
    public const BLOCKS = [
        ['kind' => 'callout', 'tone' => 'good', 'title' => 'The short version',
            'text' => "Tell Anee the crop, its variety and age, the problem, what you see and what you already did. Add a clear photo. Ask one thing at a time. The more detail you give, the better the answer, and the fewer credits it takes to get there."],

        ['kind' => 'heading', 'text' => 'Why the details matter'],
        ['kind' => 'text', 'text' => "Every question uses credits, a clear one and a vague one alike. A vague question like \"my rice is sick\" can only get a general answer, and it usually takes two or three more questions before you get anywhere. A detailed question gets a real answer the first time, so it costs you less in the end.\n\nA wrong or unclear question can waste your credits. Take a minute to write it well."],

        ['kind' => 'heading', 'text' => 'What to put in every question'],
        ['kind' => 'tips', 'items' => [
            'Crop and variety: rice NSIC Rc222, yellow corn P3585, Carabao mango.',
            'Age: the day count (DAS 45, DAT 30, DAP 40) or the stage (tillering, flowering, fruiting).',
            'The problem: what is wrong, in your own words.',
            'Observations: the color, the spots, where on the plant (old or new leaves, tips or edges), how many plants, and whether it is spreading.',
            'What you already did: the fertilizer, spray or water, with the product name, the rate and the date.',
            'The conditions: recent rain, heat, flooding or a dry spell, and your soil if you know it.',
            'What you want: the one thing you need to know or decide.',
        ]],

        ['kind' => 'heading', 'text' => 'Good and bad questions'],
        ['kind' => 'callout', 'tone' => 'warn', 'title' => 'Not like this',
            'text' => "\"My rice is sick.\"\n\"What fertilizer should I use?\""],
        ['kind' => 'callout', 'tone' => 'good', 'title' => 'Like this',
            'text' => "\"RC222 ang tanim ko, 45 DAT. Naninilaw ang gilid ng mga dahon, lalo na sa mga lumang dahon. Nag-urea ako 10 araw na ang nakalipas, isang sako bawat ektarya. Sobrang maulan nitong linggo. Ano ang problema at ano ang dapat kong gawin?\""],
        ['kind' => 'callout', 'tone' => 'good', 'title' => 'Or like this',
            'text' => "\"Yellow corn P3585, 40 days after planting. The lower leaves are yellowing from the tip down in a V shape; the new leaves are still green. I side dressed 2 bags of urea per hectare two weeks ago and it has rained hard since. Is it nitrogen, and should I apply more?\""],

        ['kind' => 'heading', 'text' => 'Sending photos'],
        ['kind' => 'steps', 'items' => [
            'Take the photo in daylight, not at night and not in the harsh noon sun.',
            'Get close, so the spots, the insects or the damage fill the picture.',
            'Add one wider photo, so Anee can see how much of the field is affected.',
            'Hold still and tap the screen to focus, so the photo is sharp.',
            'Still write the details. A photo shows what it looks like; your words tell when it started, how much of the field, and what you did.',
        ]],

        ['kind' => 'heading', 'text' => 'One question at a time'],
        ['kind' => 'text', 'text' => "Ask about one problem in each message. If you have three problems, send three short questions. Each answer stays clear, and you can follow up on each one."],

        ['kind' => 'heading', 'text' => 'Asking from your season'],
        ['kind' => 'text', 'text' => "When you ask from inside a cropping schedule, Anee already knows that season's crop, variety and lots. You can also attach the whole season, so she reads the work you logged, your notes and where each lot stands today. That makes the question longer and uses more credits, so attach it when the question needs it. Either way, still say which lot you mean and what you see."],

        ['kind' => 'heading', 'text' => 'What Anee will not do'],
        ['kind' => 'tips', 'items' => [
            'Predict the future. Anee will not guess how much your field will yield, what the price will be, or whether a crop will make it, even from a photo. She only answers what is accurate and scientifically based. Ask instead what is limiting the crop now and how to protect your harvest.',
            'Answer questions outside farming.',
            'Remember other chats. Each new chat starts fresh, so give the details again when you start one.',
            'Replace a field visit when it is serious. For a disease or pest that is spreading fast, ask your local agriculturist to see the field too.',
        ]],

        ['kind' => 'heading', 'text' => 'Following up'],
        ['kind' => 'tips', 'items' => [
            'If part of the answer is not clear, ask about that one part.',
            'If Anee asks you a question back, answer it. That one detail usually decides the answer.',
            'Tell her what happened after you tried her advice, and ask what to do next.',
        ]],

        ['kind' => 'callout', 'tone' => 'note', 'title' => 'Save your credits',
            'text' => "Spell product names carefully, give numbers with their units (bags per hectare, ml per 16 liter tank), and read the whole answer before you ask again."],
    ];
}
