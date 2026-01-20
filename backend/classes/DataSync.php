<?php
/**
 * Класс для работы с официальным API Портала открытых данных Москвы
 * Датасет 1231 — "Открытые ледовые катки"
 */
require_once __DIR__ . '/Database.php';

class DataSync {
    private $db;
    private $apiKey = '0194edde-9eb7-4a8a-ae8e-350a445d86b2'; 
    const DATASET_ID = 1231;

    public function __construct() {
        $this->db = Database::getInstance();
        ini_set('memory_limit', '512M');
        set_time_limit(0); 
    }

    public function getCount() {
        $url = "https://apidata.mos.ru/v1/datasets/" . self::DATASET_ID . "/count?api_key={$this->apiKey}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return intval($response) ?: 0;
    }

    public function syncBatch($top = 500, $skip = 0) {
        $url = "https://apidata.mos.ru/v1/datasets/" . self::DATASET_ID . "/features?api_key={$this->apiKey}&\$top={$top}&\$skip={$skip}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Ошибка API (Код {$httpCode})");
        }

        $json = json_decode($response, true);
        if (!$json || !isset($json['features'])) {
            return 0;
        }

        $processed = 0;
        foreach ($json['features'] as $feature) {
            // ИСПРАВЛЕНО: attributes с маленькой буквы!
            $attr = $feature['properties']['attributes'] ?? [];
            $coords = $feature['geometry']['coordinates'] ?? [null, null];
            
            // Координаты в GeoJSON: [longitude, latitude]
            $longitude = $coords[0];
            $latitude = $coords[1];
            
            // Маппинг под РЕАЛЬНУЮ структуру данных
            $data = [
                'name' => $attr['ObjectName'] ?? $attr['NameWinter'] ?? 'Каток',
                'address' => $attr['Address'] ?? 'Адрес не указан',
                'district' => $attr['District'] ?? $attr['AdmArea'] ?? 'Район не указан',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'is_paid' => (isset($attr['Paid']) && mb_strtolower($attr['Paid']) !== 'бесплатно') ? 1 : 0,
                'working_hours' => $this->extractWorkingHours($attr),
                'has_equipment_rental' => $this->checkYes($attr['HasEquipmentRental'] ?? ''),
                'has_locker_room' => $this->checkYes($attr['HasDressingRoom'] ?? ''),
                'has_cafe' => $this->checkYes($attr['HasEatery'] ?? ''),
                'has_wifi' => $this->checkYes($attr['HasWifi'] ?? ''),
                'has_atm' => $this->checkYes($attr['HasCashMachine'] ?? ''),
                'has_medpoint' => $this->checkYes($attr['HasFirstAidPost'] ?? ''),
                'is_disabled_accessible' => (isset($attr['DisabilityFriendly']) && mb_strtolower($attr['DisabilityFriendly']) !== 'не приспособлен') ? 1 : 0
            ];

            if ($this->upsertRink($data)) {
                $processed++;
            }
        }

        return $processed;
    }

    private function checkYes($value) {
        return (mb_strtolower(trim($value)) === 'да') ? 1 : 0;
    }

    private function extractWorkingHours($attr) {
        if (isset($attr['WorkingHoursWinter']) && is_array($attr['WorkingHoursWinter']) && count($attr['WorkingHoursWinter']) > 0) {
            $first = $attr['WorkingHoursWinter'][0];
            if (isset($first['Hours'])) {
                return $first['Hours'];
            }
        }
        return '10:00-22:00';
    }

    private function upsertRink($data) {
        // Теперь НЕ пропускаем записи без координат — просто ставим NULL
        if (empty($data['name'])) return false;

        $existing = $this->db->fetchOne(
            "SELECT id FROM rinks WHERE name = ? AND address = ?",
            [$data['name'], $data['address']]
        );

        if ($existing) {
            $this->db->query(
                "UPDATE rinks SET 
                    district = ?, latitude = ?, longitude = ?, is_paid = ?, 
                    working_hours = ?, has_equipment_rental = ?, has_locker_room = ?, 
                    has_cafe = ?, has_wifi = ?, has_atm = ?, has_medpoint = ?,
                    is_disabled_accessible = ?, last_sync = CURRENT_TIMESTAMP
                 WHERE id = ?",
                [
                    $data['district'], $data['latitude'], $data['longitude'], $data['is_paid'],
                    $data['working_hours'], $data['has_equipment_rental'], $data['has_locker_room'],
                    $data['has_cafe'], $data['has_wifi'], $data['has_atm'], $data['has_medpoint'],
                    $data['is_disabled_accessible'], $existing['id']
                ]
            );
        } else {
            $this->db->insert(
                "INSERT INTO rinks (name, address, district, latitude, longitude, is_paid, working_hours, 
                                  has_equipment_rental, has_locker_room, has_cafe, has_wifi,
                                  has_atm, has_medpoint, is_disabled_accessible)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['name'], $data['address'], $data['district'], $data['latitude'], $data['longitude'],
                    $data['is_paid'], $data['working_hours'], $data['has_equipment_rental'],
                    $data['has_locker_room'], $data['has_cafe'], $data['has_wifi'],
                    $data['has_atm'], $data['has_medpoint'], $data['is_disabled_accessible']
                ]
            );
        }
        return true;
    }
}
