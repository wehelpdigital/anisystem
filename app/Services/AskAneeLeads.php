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
 *                         AniSystem store, with the question and the farm as
 *                         custom data and one activity line saying what they
 *                         asked. The same email again adds a line to the lead
 *                         it already is, never a second lead.
 *
 * A lead, not a client: nobody gets an account here. Each half is tried on
 * its own and a failure is logged, never shown: the visitor's answer does
 * not wait on the marketing side.
 */
class AskAneeLeads
{
    public const SOURCE = 'Ask Anee';

    public function capture(AsAskQuestion $q, string $answerUrl): void
    {
        try {
            $listed = app(AcumbamailService::class)->addLead((string) $q->email, [
                (string) config('acumbamail.fields.first_name') => $this->firstName((string) $q->email),
            ]);
            if ($listed) {
                $q->forceFill(['listedAt' => now()])->save();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            $leadId = $this->crm($q, $answerUrl);
            if ($leadId) {
                $q->forceFill(['crmLeadId' => $leadId])->save();
            }
        } catch (\Throwable $e) {
            Log::warning('Ask Anee: CRM lead not written', ['id' => $q->id, 'error' => $e->getMessage()]);
        }
    }

    private function crm(AsAskQuestion $q, string $answerUrl): ?int
    {
        $owner = $this->owner();
        if (! $owner) {
            return null;
        }
        $now = now();
        $source = $this->source();
        $country = $q->country && $q->country !== 'PH' ? (\App\Support\Region::name($q->country) ?? $q->country) : 'Philippines';
        $facts = array_filter([
            'Ask Anee question' => $q->question,
            'Crop' => $q->cropLabel,
            'Farm size' => $q->farmWords(),
            'Province' => $q->province,
            'Town' => $q->town,
            'Answer link' => $answerUrl,
        ], fn ($v) => trim((string) $v) !== '');

        $lead = DB::table('crm_leads')->where('delete_status', 'active')->where('email', $q->email)->orderByDesc('id')->first();
        if (! $lead) {
            $leadId = DB::table('crm_leads')->insertGetId([
                'usersId' => $owner,
                'leadStatus' => 'new',
                'leadPriority' => 'medium',
                'leadSourceId' => $source,
                'leadSourceOther' => self::SOURCE,
                'firstName' => mb_substr($this->firstName((string) $q->email), 0, 100),
                'lastName' => '',
                'email' => $q->email,
                'province' => $q->province ? mb_substr($q->province, 0, 100) : null,
                'municipality' => $q->town ? mb_substr($q->town, 0, 100) : null,
                'country' => $country,
                'notes' => 'Asked Anee on the website: "' . mb_substr($q->question, 0, 500) . '"',
                'delete_status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $store = DB::table('ecom_product_stores')->where('storeName', 'AniSystem')->value('id');
            if ($store) {
                DB::table('crm_lead_store_targets')->insert(['leadId' => $leadId, 'storeId' => $store, 'created_at' => $now, 'updated_at' => $now]);
            }
        } else {
            $leadId = (int) $lead->id;
            DB::table('crm_leads')->where('id', $leadId)->update(['updated_at' => $now]);
        }

        foreach ($facts as $name => $value) {
            DB::table('crm_lead_custom_data')->insert([
                'leadId' => $leadId, 'fieldName' => $name, 'fieldValue' => mb_substr((string) $value, 0, 2000),
                'usersId' => $owner, 'delete_status' => 'active', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        DB::table('crm_lead_activities')->insert([
            'leadId' => $leadId,
            'usersId' => $owner,
            'activityType' => 'note',
            'activitySubject' => 'Asked Anee on the website',
            'activityDescription' => '"' . $q->question . '"'
                . ($q->cropLabel ? "\nCrop: " . $q->cropLabel : '')
                . ($q->farmWords() ? "\nFarm: " . $q->farmWords() : '')
                . ($q->placeWords() ? "\nLocation: " . $q->placeWords() : '')
                . "\nAnswer: " . $answerUrl,
            'activityDate' => $now,
            'delete_status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $leadId;
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

    /** "juan.delacruz88@..." reads as "Juan Delacruz". */
    public function firstName(string $email): string
    {
        $local = strtolower((string) strstr($email, '@', true));
        $words = array_filter(preg_split('/[^a-z]+/', $local) ?: [], fn ($w) => strlen($w) > 1);

        return $words ? ucwords(implode(' ', array_slice($words, 0, 2))) : 'Ask Anee visitor';
    }
}
