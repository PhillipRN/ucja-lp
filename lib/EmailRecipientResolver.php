<?php

class EmailRecipientResolver {
    private $supabase;
    private $applicationCache = [];
    private $individualCache = [];
    private $teamCache = [];
    private $teamMembersCache = [];
    private $teamMemberDetailCache = [];

    private const RECIPIENT_ORDER = ['guardian', 'participant', 'team_members'];

    public function __construct(SupabaseClient $supabase) {
        $this->supabase = $supabase;
    }

    /**
     * Resolve recipients based on application id and recipient type.
     *
     * @param string $applicationId
     * @param string $type guardian | participant | guardian_and_participant | team_members | custom | combinations
     * @return array [['email' => '', 'name' => ''], ...]
     */
    public function resolveRecipients($applicationId, $type = 'guardian', $options = []) {
        $application = $this->getApplication($applicationId);
        if (!$application) {
            return [];
        }

        $memberId = $options['team_member_id'] ?? null;
        $recipientTokens = $this->expandRecipientTypes($type);

        $recipients = [];
        foreach ($recipientTokens as $token) {
            switch ($token) {
                case 'guardian':
                    $recipients[] = $this->getGuardianRecipient($application);
                    break;
                case 'participant':
                case 'student':
                    $recipients[] = $this->getParticipantRecipient($application, $memberId);
                    break;
                case 'team_members':
                    $recipients = array_merge($recipients, $this->getTeamMembersRecipients($application));
                    break;
                default:
                    break;
            }
        }

        // Fallback to guardian if nothing resolved
        if (empty(array_filter($recipients))) {
            $recipients[] = $this->getGuardianRecipient($application);
        }

        return $this->uniqueRecipients($recipients);
    }

    private function expandRecipientTypes(?string $type): array {
        if (!$type) {
            return ['guardian'];
        }

        $type = trim($type);
        if ($type === '' || $type === 'custom') {
            return ['guardian'];
        }

        $legacyMap = [
            'guardian_and_participant' => ['guardian', 'participant'],
            'guardian_and_participant_and_team_members' => ['guardian', 'participant', 'team_members'],
            'guardian_and_team_members' => ['guardian', 'team_members'],
            'participant_and_team_members' => ['participant', 'team_members'],
            'student' => ['participant']
        ];

        if (isset($legacyMap[$type])) {
            return $legacyMap[$type];
        }

        $normalized = str_replace('_and_', ',', $type);
        $parts = array_filter(array_map('trim', explode(',', $normalized)));

        if (empty($parts)) {
            return ['guardian'];
        }

        $allowed = array_merge(self::RECIPIENT_ORDER, array_keys($legacyMap));
        $tokens = [];

        foreach ($parts as $part) {
            if (!in_array($part, $allowed, true)) {
                continue;
            }

            $resolved = $legacyMap[$part] ?? [$part];
            foreach ($resolved as $token) {
                if (!in_array($token, $tokens, true)) {
                    $tokens[] = $token;
                }
            }
        }

        if (empty($tokens)) {
            return ['guardian'];
        }

        // Ensure deterministic order
        usort($tokens, function ($a, $b) {
            $orderA = array_search($a, self::RECIPIENT_ORDER, true);
            $orderB = array_search($b, self::RECIPIENT_ORDER, true);
            return $orderA <=> $orderB;
        });

        return $tokens;
    }

    private function getApplication($applicationId) {
        if (isset($this->applicationCache[$applicationId])) {
            return $this->applicationCache[$applicationId];
        }

        $result = $this->supabase->from('applications')
            ->select('*')
            ->eq('id', $applicationId)
            ->single();

        if ($result['success'] && !empty($result['data'])) {
            $this->applicationCache[$applicationId] = $result['data'];
            return $result['data'];
        }

        return null;
    }

    private function getGuardianRecipient($application) {
        if ($application['participation_type'] === 'individual') {
            $individual = $this->getIndividualApplication($application['id']);
            if ($individual && !empty($individual['guardian_email'])) {
                return [
                    'email' => $individual['guardian_email'],
                    'name' => $individual['guardian_name'] ?? ''
                ];
            }
        } else {
            $team = $this->getTeamApplication($application['id']);
            if ($team && !empty($team['guardian_email'])) {
                return [
                    'email' => $team['guardian_email'],
                    'name' => $team['guardian_name'] ?? ($team['team_name'] ?? '')
                ];
            }
        }

        return null;
    }

    private function getParticipantRecipient($application, $specificMemberId = null) {
        if ($application['participation_type'] === 'individual') {
            $individual = $this->getIndividualApplication($application['id']);
            if ($individual && !empty($individual['student_email'])) {
                return [
                    'email' => $individual['student_email'],
                    'name' => $individual['student_name'] ?? ''
                ];
            }
        } else {
            if ($specificMemberId) {
                $member = $this->getTeamMemberById($specificMemberId);
                if ($member) {
                    $teamApp = $this->getTeamApplication($application['id']);
                    if ($teamApp && $member['team_application_id'] === $teamApp['id'] && !empty($member['member_email'])) {
                        return [
                            'email' => $member['member_email'],
                            'name' => $member['member_name'] ?? ''
                        ];
                    }
                }
            }

            $representative = $this->getTeamRepresentative($application['id']);
            if ($representative) {
                return $representative;
            }
        }

        return null;
    }

    private function getTeamMemberById($memberId) {
        if (isset($this->teamMemberDetailCache[$memberId])) {
            return $this->teamMemberDetailCache[$memberId];
        }

        $result = $this->supabase->from('team_members')
            ->select('*')
            ->eq('id', $memberId)
            ->single();

        if ($result['success'] && !empty($result['data'])) {
            $this->teamMemberDetailCache[$memberId] = $result['data'];
            return $result['data'];
        }

        return null;
    }

    private function getTeamMembersRecipients($application) {
        if ($application['participation_type'] !== 'team') {
            return [];
        }

        $team = $this->getTeamApplication($application['id']);
        if (!$team) {
            return [];
        }

        $teamApplicationId = $team['id'];
        $members = $this->getTeamMembers($teamApplicationId);

        $recipients = [];
        foreach ($members as $member) {
            if (empty($member['member_email'])) {
                continue;
            }
            $recipients[] = [
                'email' => $member['member_email'],
                'name' => $member['member_name'] ?? ''
            ];
        }

        return $this->uniqueRecipients($recipients);
    }

    private function getIndividualApplication($applicationId) {
        if (isset($this->individualCache[$applicationId])) {
            return $this->individualCache[$applicationId];
        }

        $result = $this->supabase->from('individual_applications')
            ->select('*')
            ->eq('application_id', $applicationId)
            ->single();

        if ($result['success'] && !empty($result['data'])) {
            $this->individualCache[$applicationId] = $result['data'];
            return $result['data'];
        }

        return null;
    }

    private function getTeamApplication($applicationId) {
        if (isset($this->teamCache[$applicationId])) {
            return $this->teamCache[$applicationId];
        }

        $result = $this->supabase->from('team_applications')
            ->select('*')
            ->eq('application_id', $applicationId)
            ->single();

        if ($result['success'] && !empty($result['data'])) {
            $this->teamCache[$applicationId] = $result['data'];
            return $result['data'];
        }

        return null;
    }

    private function getTeamRepresentative($applicationId) {
        $team = $this->getTeamApplication($applicationId);
        if (!$team) {
            return null;
        }

        $teamApplicationId = $team['id'];
        $members = $this->getTeamMembers($teamApplicationId);

        foreach ($members as $member) {
            if (!empty($member['is_representative']) && !empty($member['member_email'])) {
                return [
                    'email' => $member['member_email'],
                    'name' => $member['member_name'] ?? ''
                ];
            }
        }

        // Fallback to first member with email
        foreach ($members as $member) {
            if (!empty($member['member_email'])) {
                return [
                    'email' => $member['member_email'],
                    'name' => $member['member_name'] ?? ''
                ];
            }
        }

        return null;
    }

    private function getTeamMembers($teamApplicationId) {
        if (isset($this->teamMembersCache[$teamApplicationId])) {
            return $this->teamMembersCache[$teamApplicationId];
        }

        $result = $this->supabase->from('team_members')
            ->select('*')
            ->eq('team_application_id', $teamApplicationId)
            ->order('member_number', true)
            ->execute();

        if ($result['success'] && !empty($result['data'])) {
            $this->teamMembersCache[$teamApplicationId] = $result['data'];
            return $result['data'];
        }

        return [];
    }

    private function uniqueRecipients(array $recipients) {
        $unique = [];
        $seen = [];

        foreach ($recipients as $recipient) {
            if (!$recipient || empty($recipient['email'])) {
                continue;
            }

            $emailKey = strtolower(trim($recipient['email']));
            if (isset($seen[$emailKey])) {
                continue;
            }

            $seen[$emailKey] = true;
            $unique[] = [
                'email' => $recipient['email'],
                'name' => $recipient['name'] ?? ''
            ];
        }

        return $unique;
    }
}

