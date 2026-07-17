<?php

namespace App\Support;

/**
 * Sanitizes client-authored rich text (activity descriptions, version notes)
 * before it is stored. These rows are shared with the mother admin app and
 * rendered raw ({!! !!}) in the timeline, exports, card viewer and worker
 * presentation — so unsanitized client HTML would be stored XSS that executes
 * in a super-admin's browser. We allow only the formatting tags the Quill
 * editor produces and strip scripts, event handlers and dangerous URIs.
 */
class HtmlSanitizer
{
    private static ?\HTMLPurifier $purifier = null;

    public static function rich(?string $html): string
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }

        return self::purifier()->purify($html);
    }

    private static function purifier(): \HTMLPurifier
    {
        if (self::$purifier !== null) {
            return self::$purifier;
        }

        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        // Quill / basic formatting tags only.
        $config->set('HTML.Allowed',
            'p,br,span[style],strong,b,em,i,u,s,strike,'
            .'ul,ol,li,blockquote,pre,code,'
            .'h1,h2,h3,h4,h5,h6,'
            .'a[href|title|target|rel],img[src|alt|width|height]'
        );
        $config->set('CSS.AllowedProperties', 'color,background-color,text-align,font-weight,font-style,text-decoration');
        // Links: http/https/mailto only; force safe rel/target.
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('HTML.TargetBlank', true);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);

        $cacheDir = storage_path('app/htmlpurifier');
        if (! is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }
        $config->set('Cache.SerializerPath', $cacheDir);

        return self::$purifier = new \HTMLPurifier($config);
    }
}
