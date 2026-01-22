<?php
/**
 * Grade helpers: compute percentages and letter grades
 */

/**
 * Compute a final percent for a course's CA based on assessments and scores.
 * - If any assessment has a numeric weight, compute weighted percent as sum(percent_i * weight_i/100)
 *   and track total weight coverage (sum of weights that had scores and were numeric).
 * - If no weights used (coverage == 0), fallback to plain percent from sums of scores/max.
 *
 * @param array $assessments Array of assessments (rows with keys: id, max_score, weight)
 * @param array $scores Map assessment_id => numeric score
 * @return array { 'mode': 'weighted'|'plain'|'none', 'percent': float|null, 'coverage': float, 'plain_percent': float|null }
 */
function compute_ca_percent(array $assessments, array $scores) {
    $total_weight = 0.0; $weighted_sum = 0.0;
    $plain_sum = 0.0; $plain_max = 0.0;

    foreach ($assessments as $a) {
        $aid = intval($a['id']);
        $max = isset($a['max_score']) ? floatval($a['max_score']) : 0.0;
        $w = (isset($a['weight']) && $a['weight'] !== '' && $a['weight'] !== null) ? floatval($a['weight']) : null;
        if (!array_key_exists($aid, $scores)) {
            // No score recorded, skip but still count toward plain_max if desired? We'll skip.
            continue;
        }
        $sc = floatval($scores[$aid]);
        if ($max > 0) {
            $pct = ($sc / $max) * 100.0;
            if ($w !== null) {
                $weighted_sum += $pct * ($w / 100.0);
                $total_weight += $w;
            }
            $plain_sum += $sc;
            $plain_max += $max;
        }
    }

    $plain_percent = ($plain_max > 0) ? ($plain_sum / $plain_max) * 100.0 : null;

    if ($total_weight > 0) {
        return [
            'mode' => 'weighted',
            'percent' => $weighted_sum,
            'coverage' => $total_weight,
            'plain_percent' => $plain_percent,
        ];
    }
    if ($plain_percent !== null) {
        return [
            'mode' => 'plain',
            'percent' => $plain_percent,
            'coverage' => 0.0,
            'plain_percent' => $plain_percent,
        ];
    }
    return [
        'mode' => 'none',
        'percent' => null,
        'coverage' => 0.0,
        'plain_percent' => null,
    ];
}

/**
 * Default letter grade mapping (simple US-style scale)
 * A: >= 90, B: >= 80, C: >= 70, D: >= 60, F otherwise
 *
 * @param float|null $percent
 * @return string
 */
function letter_grade($percent) {
    if ($percent === null) return 'N/A';
    if ($percent >= 90) return 'A';
    if ($percent >= 80) return 'B';
    if ($percent >= 70) return 'C';
    if ($percent >= 60) return 'D';
    return 'F';
}
