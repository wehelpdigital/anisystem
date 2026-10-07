<?php

namespace App\Support;

/**
 * Words for the public site's pictures that were drawn as decoration, with
 * an empty alt (2026-10-07). A site audit asked for alt and title words on
 * every picture, readable and with the words a farmer searches for, so
 * PublicSeoTags gives such a picture these words and keeps it out of a
 * screen reader's way (aria-hidden), as its empty alt did.
 *
 * Keyed by the path under /images/, without a query string. A picture not
 * listed gets words from its file name (see forPath).
 */
class ImageWords
{
    public const WORDS = [
        'anee-song-poster.jpg' => 'Anee, the anee.io smart farm technician, in the anee.io farm song video',
        'anee/avatar-160.jpg' => 'Anee, the anee.io smart farm technician',
        'anee/avatar-512.jpg' => 'Anee, the anee.io smart farm technician for Filipino farmers',
        'anee/emoji/concerned.png' => 'Anee looking concerned about a problem in the field',
        'anee/emoji/delighted.png' => 'Anee delighted with a good harvest',
        'anee/emoji/happy.png' => 'Anee, the smart farm technician, smiling',
        'anee/emoji/salute.png' => 'Anee giving a salute, ready to help on the farm',
        'anee/emoji/starstruck.png' => 'Anee starstruck by a healthy crop',
        'anee/emoji/thinking.png' => 'Anee thinking about your farm question',
        'anee/emoji/thumbsup.png' => 'Anee giving a thumbs up',
        'logo.png' => 'anee.io smart farm app logo',
        'logo-mark.png' => 'anee.io logo mark',
        'site/logo-white.png' => 'anee.io smart farm app logo',
        'top-yield.webp' => 'A rice field at top yield, ready for harvest',
        'palay-08.jpg' => 'Ripe palay in a palayan, ready for the ani',
        'newspaper.png' => 'Farm news icon',

        // The feature icons.
        'appointment.png' => 'Cropping calendar schedule icon',
        'card-index.png' => 'Farm records icon',
        'community-post.png' => 'Farmer community post icon',
        'document.png' => 'Farm documentation icon',
        'friends.png' => 'Farm workers and team icon',
        'gallery.png' => 'Farm photo gallery icon',
        'icons/ab-testing.png' => 'Compare farm reports icon',
        'icons/biostimulant.png' => 'Foliar fertilizer and biostimulant icon',
        'icons/biotechnology.png' => 'Crop variety research icon',
        'icons/bricks.png' => 'Protocol builder icon',
        'icons/calendar.png' => 'Cropping calendar icon',
        'icons/chat.png' => 'Chat with Anee, the smart farm technician, icon',
        'icons/checklist.png' => 'Daily farm tasks checklist icon',
        'icons/fertilizer.png' => 'Fertilizer plan icon',
        'icons/money-bag.png' => 'Farm expenses and profit icon',
        'icons/npk.svg' => 'NPK Plus fertilizer calculator icon',
        'icons/offline.png' => 'Offline farm app icon',
        'icons/pest.svg' => 'Pest and disease finder icon',
        'icons/profit.png' => 'Farm profit report icon',
        'icons/satellite.svg' => 'Satellite analysis of your field icon',
        'icons/soil-restoration.png' => 'Soil and land preparation icon',
        'icons/stash.svg' => 'The Stash, farm guides shared by partners, icon',
        'icons/storm.svg' => 'Satellite weather and typhoon watch icon',
        'icons/tea.png' => 'Farm tip of the day icon',
        'icons/technician-support.png' => 'Smart farm technician support icon',
        'icons/tool-box.png' => 'Farm tools icon',
        'idea.png' => 'Farm idea icon',
        'list.png' => 'Farm task list icon',
        'location-marker.png' => 'Farm map pin icon',
        'pencil.png' => 'Farm drawing icon',
        'pie-chart.png' => 'Farm reports chart icon',
        'plant.png' => 'Crop growth stages icon',
        'sack.png' => 'Harvest records icon',
        'speech-bubbles.png' => 'Farmer community chat icon',
        'time.png' => 'Farm timeline icon',
        'tractor.png' => 'Farm machinery icon',
        'treasure-map.png' => 'Farm map icon',
        'user-refresh.png' => 'Team logins icon',
        'voice-recorder.png' => 'Voice notes icon',
        'weather.png' => 'Farm weather forecast icon',
        'writting.png' => 'Farm notes icon',

        // The photographs.
        'site/corn-rows.jpg' => 'Even rows of young corn in a Philippine field',
        'site/palay.jpg' => 'A palay field in the Philippines',
        'site/fields-aerial.jpg' => 'Aerial view of rice paddies and bunds in the Philippines',
        'site/photos/farmer-hijab.jpg' => 'A Filipino farmer checking her crops',
        'site/photos/hero-planting.jpg' => 'Filipino farmers planting rice in a flooded paddy',
        'site/photos/inspect.jpg' => 'A farmer checking her rice field with the anee.io app',
        'site/photos/palay-heads.jpg' => 'Golden palay heads ready for harvest',
        'site/photos/palay-phone.jpg' => 'A farmer holding a phone over a palay field',
        'site/photos/sacks-shed.jpg' => 'Sacks of harvested palay in a farm shed',
        'site/photos/sacks.jpg' => 'Sacks of palay after the harvest',
        'site/photos/storm-paddies.jpg' => 'Rice paddies under a dark storm sky',
        'site/photos/transplant.jpg' => 'Farmers transplanting rice seedlings',
        'site/photos/team-thumbs.jpg' => 'Two farmers giving a thumbs up beside their rice field',
        'site/storm/ph-satellite.webp' => 'Satellite view of a typhoon over the Philippines',
        'site/home-crops/palay.webp' => 'Ripe palay heads in a rice field',
        'site/home-crops/mais.webp' => 'Young corn plants in rows',
        'site/home-crops/gulay.webp' => 'Vegetable farms on a mountain slope',
        'site/home-crops/puno.webp' => 'Banana trees along a farm road',
        'site/home-team/cam-1.webp' => 'A farm worker sends a live photo from the rice field',
        'site/home-team/cam-2.webp' => 'A live photo of a lot from a farm worker',
        'site/home-team/field.webp' => 'A rice field seen from the farm road',
        'site/home-team/palay-heads.webp' => 'Palay heads filling with grain',
        'site/home-team/sat-field.webp' => 'Satellite view of a farm field',
        'site/home-team/sat-health.webp' => 'Satellite crop health map of a rice field',
        'site/home-team/sat.webp' => 'Satellite map of farm lots',
        'site/home-team/shed.webp' => 'A farm shed with the season\'s harvest',
        'site/lp/palay-phone.webp' => 'A farmer holding a phone over a palay field',
    ];

    /** Readable words for a picture's address (absolute or not), or ''. */
    public static function forSrc(string $src): string
    {
        $path = parse_url(html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8'), PHP_URL_PATH) ?: '';
        $at = strpos($path, '/images/');
        if ($at === false) {
            return '';
        }

        return self::forPath(substr($path, $at + strlen('/images/')));
    }

    public static function forPath(string $path): string
    {
        $path = ltrim($path, '/');
        if (isset(self::WORDS[$path])) {
            return self::WORDS[$path];
        }
        // A small copy, or a page's own copy of a photo: the words of the original.
        foreach ([preg_replace('#-480\.webp$#', '.webp', $path), preg_replace('#-480\.webp$#', '.jpg', $path),
                     preg_replace('#^site/lp/loss/(.+)\.webp$#', 'site/photos/$1.jpg', $path)] as $twin) {
            if ($twin && $twin !== $path && isset(self::WORDS[$twin])) {
                return self::WORDS[$twin];
            }
        }
        $dir = dirname($path);
        $name = preg_replace(['#-480$#', '#^(problems|crops|blog|pests|diseases|weeds)-#'], '', pathinfo($path, PATHINFO_FILENAME));
        $name = ucfirst(trim(preg_replace('#[-_]+#', ' ', $name)));
        if ($name === '') {
            return '';
        }

        return match (true) {
            $dir === 'site/pests' => $name . ', a crop pest in the Philippines',
            $dir === 'site/diseases' => $name . ', a crop disease in the Philippines',
            $dir === 'site/weeds' => $name . ', a weed on Philippine farms',
            $dir === 'site/land' => $name . ' in the Philippines',
            str_starts_with($dir, 'anee') => 'Anee, the anee.io smart farm technician',
            str_starts_with($dir, 'icons') || ! str_contains($dir, 'site') => $name . ' icon',
            default => $name,
        };
    }
}
