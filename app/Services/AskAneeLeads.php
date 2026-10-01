<?php

namespace App\Services;

use App\Models\AsAskQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * A Try and Ask Anee visitor becomes a lead in two places (2026-10-01):
 *
 *   the Acumbamail list   stamped Plan = "lead" (AcumbamailService::addLead)
 *   the mother's CRM      a crm_leads row, source "Ask Anee", aimed at the
 *                         AniSystem store, the farm as custom data
 *
 * The moment they give the farm and the email (captureFarm), before any
 * question; the question and its answer link join the same lead when the
 * answer is emailed (noteQuestion). The same email again adds lines to the
 * lead it already is, never a second lead.
 *
 * A lead, not a client: nobody gets an account here. Each half is tried on
 * its own and a failure is logged, never shown: the visitor waits on
 * neither.
 */
class AskAneeLeads
{
    public const SOURCE = 'Ask Anee';

    public function captureFarm(AsAskQuestion $q): void
    {
        [$first, $last] = $this->split((string) $q->name, (string) $q->email);
        try {
            $f = config('acumbamail.fields');
            $listed = app(AcumbamailService::class)->addLead((string) $q->email, [
                (string) $f['first_name'] => $first,
                (string) $f['last_name'] => $last,
            ]);
            if ($listed) {
                $q->forceFill(['listedAt' => now()])->save();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            $leadId = $this->lead($q, $first, $last);
            if (! $leadId) {
                return;
            }
            $q->forceFill(['crmLeadId' => $leadId])->save();
            $this->facts($leadId, [
                'Farm size' => $q->farmWords(),
                'Crop' => $q->cropLabel,
                'Province' => $q->province,
                'Town' => $q->town,
            ]);
            $this->activity($leadId, 'Tried Ask Anee on the website',
                'Gave their farm: ' . trim($q->farmWords() . ' of ' . $q->cropLabel . ($q->placeWords() ? ' in ' . $q->placeWords() : '')) . '.');
        } catch (\Throwable $e) {
            Log::warning('Ask Anee: CRM lead not written', ['id' => $q->id, 'error' => $e->getMessage()]);
        }
    }

    public function noteQuestion(AsAskQuestion $q, string $answerUrl): void
    {
        try {
            $leadId = $q->crmLeadId ?: $this->lead($q, ...$this->split((string) $q->name, (string) $q->email));
            if (! $leadId) {
                return;
            }
            if (! $q->crmLeadId) {
                $q->forceFill(['crmLeadId' => $leadId])->save();
            }
            $this->facts($leadId, ['Ask Anee question' => $q->question, 'Answer link' => $answerUrl]);
            $this->activity($leadId, 'Asked Anee on the website', '"' . $q->question . '"' . "\nAnswer: " . $answerUrl);
        } catch (\Throwable $e) {
            Log::warning('Ask Anee: question not added to the lead', ['id' => $q->id, 'error' => $e->getMessage()]);
        }
    }

    /** The lead for this email: the one already there, or a new one. */
    private function lead(AsAskQuestion $q, string $first, string $last): ?int
    {
        $owner = $this->owner();
        if (! $owner) {
            return null;
        }
        $now = now();
        $found = DB::table('crm_leads')->where('delete_status', 'active')->where('email', $q->email)->orderByDesc('id')->first();
        if ($found) {
            DB::table('crm_leads')->where('id', $found->id)->update(['updated_at' => $now]);

            return (int) $found->id;
        }
        $country = $q->country && $q->country !== 'PH' ? \App\Support\Region::name($q->country) : 'Philippines';
        $id = DB::table('crm_leads')->insertGetId([
            'usersId' => $owner,
            'leadStatus' => 'new',
            'leadPriority' => 'medium',
            'leadSourceId' => $this->source(),
            'leadSourceOther' => self::SOURCE,
            'firstName' => mb_substr($first, 0, 100),
            'lastName' => mb_substr($last, 0, 100),
            'email' => $q->email,
            'province' => $q->province ? mb_substr($q->province, 0, 100) : null,
            'municipality' => $q->town ? mb_substr($q->town, 0, 100) : null,
            'country' => $country,
            'notes' => 'Came in through Try and Ask Anee on anee.io.',
            'delete_status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $store = DB::table('ecom_product_stores')->where('storeName', 'AniSystem')->value('id');
        if ($store) {
            DB::table('crm_lead_store_targets')->insert(['leadId' => $id, 'storeId' => $store, 'created_at' => $now, 'updated_at' => $now]);
        }

        return $id;
    }

    private function facts(int $leadId, array $facts): void
    {
        $owner = $this->owner();
        foreach ($facts as $name => $value) {
            if (trim((string) $value) === '') {
                continue;
            }
            DB::table('crm_lead_custom_data')->insert([
                'leadId' => $leadId, 'fieldName' => $name, 'fieldValue' => mb_substr((string) $value, 0, 2000),
                'usersId' => $owner, 'delete_status' => 'active', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function activity(int $leadId, string $subject, string $text): void
    {
        DB::table('crm_lead_activities')->insert([
            'leadId' => $leadId,
            'usersId' => $this->owner(),
            'activityType' => 'note',
            'activitySubject' => $subject,
            'activityDescription' => $text,
            'activityDate' => now(),
            'delete_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Whose CRM the lead lands in: CRM_LEADS_OWNER_ID when set, otherwise
     * whoever owns the "Join Community" form (the other anee.io lead door).
     */
    private function owner(): ?int
    {
        $set = (int) config('anisystem.crm_leads_owner', 0);
        if ($set > 0) {
            return $set;
        }
        $id = DB::table('crm_forms')->where('formName', 'Join Community')->where('delete_status', 'active')->value('usersId')
            ?? DB::table('crm_forms')->where('delete_status', 'active')->orderByDesc('id')->value('usersId');

        return $id ? (int) $id : null;
    }

    /** The "Ask Anee" lead source, made the first time it is needed. */
    private function source(): int
    {
        $id = DB::table('crm_lead_sources')->where('sourceName', self::SOURCE)->where('delete_status', 'active')->value('id');
        if ($id) {
            return (int) $id;
        }

        return (int) DB::table('crm_lead_sources')->insertGetId([
            'usersId' => null,
            'sourceName' => self::SOURCE,
            'sourceDescription' => 'Asked Anee a free question on anee.io and left an email for the answer.',
            'sourceIcon' => 'mdi-robot-happy-outline',
            'sourceColor' => '#6b9f3d',
            'sourceOrder' => 0,
            'isActive' => 1,
            'isSystemDefault' => 0,
            'delete_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** [first, last] from the name they typed, or from the email when it is empty. */
    public function split(string $name, string $email): array
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name));
        if ($name === '') {
            $local = strtolower((string) strstr($email, '@', true));
            $words = array_filter(preg_split('/[^a-z]+/', $local) ?: [], fn ($w) => strlen($w) > 1);
            $name = $words ? ucwords(implode(' ', array_slice($words, 0, 2))) : 'Ask Anee visitor';
        }
        $parts = explode(' ', $name, 2);

        return [$parts[0], $parts[1] ?? ''];
    }
}
