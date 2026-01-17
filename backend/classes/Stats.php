<?php
/**
 * Класс для работы со статистикой посещаемости катков
 * Используется для визуализации данных (гистограммы, графики, тепловая карта)
 */

require_once __DIR__ . '/Database.php';

class Stats {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Получить статистику посещаемости катка
     * 
     * @param int $rinkId ID катка
     * @param string $period Период: 'day', 'week', 'month'
     * @return array Статистика посещаемости
     */
    public function getAttendanceStats($rinkId, $period = 'week') {
        $intervals = [
            'day' => 1,
            'week' => 7,
            'month' => 30
        ];
        
        $days = $intervals[$period] ?? 7;
        
        // Получаем количество отметок по дням
        $stats = $this->db->fetchAll(
            "SELECT 
                DATE(c.timestamp) as date,
                COUNT(*) as count,
                COUNT(DISTINCT v.user_id) as unique_users
             FROM checkins c
             LEFT JOIN visits v ON c.visit_id = v.id
             WHERE v.rink_id = ? 
             AND c.timestamp > DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(c.timestamp)
             ORDER BY date ASC",
            [$rinkId, $days]
        );
        
        return $stats;
    }
    
    /**
     * Получить статистику посещаемости по времени суток
     * Группировка по интервалам для гистограммы
     * 
     * @param int $rinkId ID катка
     * @param int $hours За сколько часов получать данные (по умолчанию 7 дней = 168 часов)
     * @return array Статистика по часам с группировкой по интервалам
     */
    public function getAttendanceByTime($rinkId, $hours = 168) {
        // Получаем данные по часам
        $hourlyData = $this->db->fetchAll(
            "SELECT 
                HOUR(c.timestamp) as hour,
                COUNT(*) as count
             FROM checkins c
             LEFT JOIN visits v ON c.visit_id = v.id
             WHERE v.rink_id = ? 
             AND c.timestamp > DATE_SUB(NOW(), INTERVAL ? HOUR)
             GROUP BY HOUR(c.timestamp)
             ORDER BY hour ASC",
            [$rinkId, $hours]
        );
        
        // Группируем по интервалам времени для гистограммы
        $intervals = [
            'Ночь (0-6)' => ['start' => 0, 'end' => 6],
            'Утро (6-12)' => ['start' => 6, 'end' => 12],
            'День (12-18)' => ['start' => 12, 'end' => 18],
            'Вечер (18-24)' => ['start' => 18, 'end' => 24]
        ];
        
        $grouped = [];
        foreach ($intervals as $label => $range) {
            $count = 0;
            foreach ($hourlyData as $row) {
                $hour = (int)$row['hour'];
                if ($hour >= $range['start'] && $hour < $range['end']) {
                    $count += (int)$row['count'];
                }
            }
            $grouped[] = [
                'interval' => $label,
                'count' => $count
            ];
        }
        
        return [
            'hourly' => $hourlyData,
            'grouped' => $grouped
        ];
    }
    
    /**
     * Получить статистику посещаемости по дням недели
     * 
     * @param int $rinkId ID катка
     * @param int $weeks За сколько недель получать данные (по умолчанию 4)
     * @return array Статистика по дням недели
     */
    public function getAttendanceByDay($rinkId, $weeks = 4) {
        // Получаем данные по дням недели (0 = воскресенье, 1 = понедельник, ...)
        $stats = $this->db->fetchAll(
            "SELECT 
                DAYOFWEEK(c.timestamp) - 1 as day_of_week,
                COUNT(*) as count,
                COUNT(DISTINCT v.user_id) as unique_users
             FROM checkins c
             LEFT JOIN visits v ON c.visit_id = v.id
             WHERE v.rink_id = ? 
             AND c.timestamp > DATE_SUB(NOW(), INTERVAL ? WEEK)
             GROUP BY DAYOFWEEK(c.timestamp) - 1
             ORDER BY day_of_week ASC",
            [$rinkId, $weeks]
        );
        
        // Названия дней недели
        $days = ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];
        
        $result = [];
        foreach ($stats as $row) {
            $dayNum = (int)$row['day_of_week'];
            $result[] = [
                'day' => $days[$dayNum],
                'day_of_week' => $dayNum,
                'count' => (int)$row['count'],
                'avg_users' => round((float)$row['avg_users_per_day'], 2)
            ];
        }
        
        return $result;
    }
    
    /**
     * Получить статистические показатели посещаемости
     * Среднее, медиана, мода
     * 
     * @param int $rinkId ID катка
     * @param int $days За сколько дней получать данные (по умолчанию 30)
     * @return array Статистические показатели
     */
    public function getStatisticalIndicators($rinkId, $days = 30) {
        // Получаем количество отметок по дням
        $dailyCounts = $this->db->fetchAll(
            "SELECT 
                DATE(c.timestamp) as date,
                COUNT(DISTINCT v.user_id) as count
             FROM checkins c
             LEFT JOIN visits v ON c.visit_id = v.id
             WHERE v.rink_id = ? 
             AND c.timestamp > DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(c.timestamp)",
            [$rinkId, $days]
        );
        
        if (empty($dailyCounts)) {
            return [
                'mean' => 0,
                'median' => 0,
                'mode' => 0,
                'min' => 0,
                'max' => 0
            ];
        }
        
        $counts = array_column($dailyCounts, 'count');
        
        // Среднее (mean)
        $mean = round(array_sum($counts) / count($counts), 2);
        
        // Медиана (median)
        sort($counts);
        $count = count($counts);
        $median = $count % 2 === 0 
            ? ($counts[$count / 2 - 1] + $counts[$count / 2]) / 2
            : $counts[($count - 1) / 2];
        
        // Мода (mode) - наиболее частое значение
        $frequencies = array_count_values($counts);
        arsort($frequencies);
        $mode = (int)key($frequencies);
        
        // Минимум и максимум
        $min = min($counts);
        $max = max($counts);
        
        return [
            'mean' => $mean,
            'median' => $median,
            'mode' => $mode,
            'min' => $min,
            'max' => $max,
            'total_days' => $count,
            'total_checkins' => array_sum($counts)
        ];
    }
    
    /**
     * Получить данные для тепловой карты популярности катков
     * Возвращает данные о всех катках с количеством отметок за последние 24 часа
     * 
     * @return array Список катков с данными для тепловой карты
     */
    public function getPopularityHeatmap() {
        // Получаем количество отметок за последние 24 часа для каждого катка
        $stats = $this->db->fetchAll(
            "SELECT 
                r.id,
                r.name,
                r.latitude,
                r.longitude,
                r.address,
                r.district,
                COUNT(DISTINCT v.user_id) as checkin_count,
                COUNT(DISTINCT c.id) as total_checkins
             FROM rinks r
             LEFT JOIN visits v ON r.id = v.rink_id
             LEFT JOIN checkins c ON v.id = c.visit_id 
             AND c.timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             WHERE r.latitude IS NOT NULL AND r.longitude IS NOT NULL
             GROUP BY r.id
             ORDER BY checkin_count DESC"
        );
        
        // Определяем максимальное количество отметок для нормализации
        $maxCount = 0;
        foreach ($stats as $row) {
            if ($row['checkin_count'] > $maxCount) {
                $maxCount = $row['checkin_count'];
            }
        }
        
        // Добавляем уровень популярности (от 0 до 1) для определения цвета
        $result = [];
        foreach ($stats as $row) {
            $popularity = $maxCount > 0 ? $row['checkin_count'] / $maxCount : 0;
            
            // Определяем уровень загруженности: низкая (0-0.33), средняя (0.33-0.66), высокая (0.66-1)
            $level = 'low';
            if ($popularity > 0.66) {
                $level = 'high';
            } elseif ($popularity > 0.33) {
                $level = 'medium';
            }
            
            $result[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'latitude' => (float)$row['latitude'],
                'longitude' => (float)$row['longitude'],
                'address' => $row['address'],
                'district' => $row['district'],
                'checkin_count' => (int)$row['checkin_count'],
                'popularity' => round($popularity, 2),
                'level' => $level
            ];
        }
        
        return $result;
    }
    
    /**
     * Получить гистограмму распределения загруженности по интервалам
     * Не простая столбчатая диаграмма, а именно гистограмма с группировкой
     * 
     * @param int $rinkId ID катка
     * @param int $days За сколько дней получать данные (по умолчанию 30)
     * @return array Гистограмма с интервалами
     */
    public function getLoadHistogram($rinkId, $days = 30) {
        // Получаем количество людей на катке по дням
        $dailyLoads = $this->db->fetchAll(
            "SELECT 
                DATE(c.timestamp) as date,
                COUNT(DISTINCT v.user_id) as people_count
             FROM checkins c
             LEFT JOIN visits v ON c.visit_id = v.id
             WHERE v.rink_id = ? 
             AND c.timestamp > DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(c.timestamp)",
            [$rinkId, $days]
        );
        
        // Группируем по интервалам (0-5 чел., 5-10 чел., 10-20 чел., 20+ чел.)
        $intervals = [
            '0-5' => ['start' => 0, 'end' => 5, 'count' => 0],
            '5-10' => ['start' => 5, 'end' => 10, 'count' => 0],
            '10-20' => ['start' => 10, 'end' => 20, 'count' => 0],
            '20+' => ['start' => 20, 'end' => PHP_INT_MAX, 'count' => 0]
        ];
        
        foreach ($dailyLoads as $row) {
            $count = (int)$row['people_count'];
            foreach ($intervals as $label => &$interval) {
                if ($count >= $interval['start'] && $count < $interval['end']) {
                    $interval['count']++;
                    break;
                }
            }
        }
        
        // Преобразуем в формат для визуализации
        $result = [];
        foreach ($intervals as $label => $interval) {
            $result[] = [
                'interval' => $label,
                'count' => $interval['count'],
                'frequency' => count($dailyLoads) > 0 
                    ? round($interval['count'] / count($dailyLoads) * 100, 2) 
                    : 0
            ];
        }
        
        return $result;
    }
}
