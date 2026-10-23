<?php

namespace App\Support;

use App\Models\AsProtocolVersion;

/**
 * A Protocol Builder version's Rules & notes as many entries (2026-10-24,
 * the owner: "rules and notes should be not just one wysiwyg but user can
 * add multiple with either uploaded file or wysiwyg").
 *
 * An entry is written ({id, kind: "text", title, html}) or a file ({id,
 * kind: "file", title, fileId}, the file itself kept in the version's
 * `files`). `notes` holds them in their order. A version from before has
 * only its one document and its files: they read as one written entry and
 * one entry a file. Every save also writes `rules`, the written entries one
 * after another under their titles, for whatever reads the document.
 */
final class ProtocolNotes
{
    public const MAX = 40;

    public const MAX_HTML = 300000;

    /** The entries as they stand: the kept list, any file it does not name added at the end. */
    public static function entries(AsProtocolVersion $v): array
    {
        $files = self::files($v);
        $kept = is_array($v->notes ?? null) ? $v->notes : null;
        $out = [];
        if ($kept === null) {
            $html = trim((string) ($v->rules ?? ''));
            if ($html !== '') {
                $out[] = ['id' => 'n_rules', 'kind' => 'text', 'title' => '', 'html' => $html];
            }
        } else {
            foreach ($kept as $e) {
                if (! is_array($e) || empty($e['id'])) {
                    continue;
                }
                if (($e['kind'] ?? '') === 'file') {
                    if (isset($files[(string) ($e['fileId'] ?? '')])) {
                        $out[] = ['id' => (string) $e['id'], 'kind' => 'file', 'title' => (string) ($e['title'] ?? ''), 'fileId' => (string) $e['fileId']];
                    }
                } else {
                    $out[] = ['id' => (string) $e['id'], 'kind' => 'text', 'title' => (string) ($e['title'] ?? ''), 'html' => (string) ($e['html'] ?? '')];
                }
            }
        }
        $named = array_column(array_filter($out, fn ($e) => $e['kind'] === 'file'), 'fileId');
        foreach ($files as $fid => $f) {
            if (! in_array($fid, $named, true)) {
                $out[] = ['id' => 'nf_' . $fid, 'kind' => 'file', 'title' => '', 'fileId' => $fid];
            }
        }

        return $out;
    }

    /** The version's files by id. */
    public static function files(AsProtocolVersion $v): array
    {
        $by = [];
        foreach ((array) ($v->files ?? []) as $f) {
            if (is_array($f) && ! empty($f['id'])) {
                $by[(string) $f['id']] = $f;
            }
        }

        return $by;
    }

    /**
     * What the editor sent, held to the rules: known kinds, a file entry only
     * for a file the version has, the words sanitised, at most MAX entries.
     */
    public static function clean(array $input, AsProtocolVersion $v): array
    {
        $files = self::files($v);
        $out = [];
        $seen = [];
        foreach (array_slice(array_values($input), 0, self::MAX) as $e) {
            if (! is_array($e)) {
                continue;
            }
            $id = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($e['id'] ?? '')) ?: 'n_' . bin2hex(random_bytes(5));
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $title = mb_substr(trim(strip_tags((string) ($e['title'] ?? ''))), 0, 160);
            if (($e['kind'] ?? '') === 'file') {
                $fid = (string) ($e['fileId'] ?? '');
                if (isset($files[$fid])) {
                    $out[] = ['id' => $id, 'kind' => 'file', 'title' => $title, 'fileId' => $fid];
                }

                continue;
            }
            $html = HtmlSanitizer::rich(mb_substr((string) ($e['html'] ?? ''), 0, self::MAX_HTML));
            if (self::blank($html)) {
                $html = '';
            }
            $out[] = ['id' => $id, 'kind' => 'text', 'title' => $title, 'html' => $html];
        }

        return $out;
    }

    public static function blank(string $html): bool
    {
        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'), " \t\n\r\0\x0B\xC2\xA0") === '';
    }

    /** The written entries as one document, each under its title: what `rules` keeps. */
    public static function compose(array $entries): ?string
    {
        $parts = [];
        foreach ($entries as $e) {
            if (($e['kind'] ?? '') !== 'text' || self::blank((string) ($e['html'] ?? ''))) {
                continue;
            }
            $parts[] = (trim((string) $e['title']) !== '' ? '<h3>' . htmlspecialchars((string) $e['title'], ENT_QUOTES, 'UTF-8') . '</h3>' : '') . $e['html'];
        }

        return $parts ? implode('<hr>', $parts) : null;
    }

    /** Keep these entries on the version (and its document beside them). Not saved: the caller saves. */
    public static function put(AsProtocolVersion $v, array $entries): void
    {
        $v->forceFill(['notes' => array_values($entries), 'rules' => self::compose($entries)]);
    }

    /** A written entry added at the end (a plan's recommendations), once by its title. Returns whether it was added. */
    public static function append(AsProtocolVersion $v, string $title, string $html): bool
    {
        $entries = self::entries($v);
        foreach ($entries as $e) {
            if ($e['kind'] === 'text' && $e['title'] === $title) {
                return false;
            }
        }
        $entries[] = ['id' => 'n_' . bin2hex(random_bytes(5)), 'kind' => 'text', 'title' => mb_substr($title, 0, 160), 'html' => HtmlSanitizer::rich($html)];
        self::put($v, $entries);

        return true;
    }

    /** A new version's first entry, as a value for `notes`. */
    public static function first(string $title, string $html): array
    {
        return [['id' => 'n_' . bin2hex(random_bytes(5)), 'kind' => 'text', 'title' => mb_substr($title, 0, 160), 'html' => HtmlSanitizer::rich($html)]];
    }
}
