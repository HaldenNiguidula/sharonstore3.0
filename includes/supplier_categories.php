<?php
declare(strict_types=1);

function cleanSupplierCategories(array $categories): array {
    $clean = [];
    foreach ($categories as $category) {
        if (!is_string($category)) throw new InvalidArgumentException('Invalid supplier category.');
        $category = trim($category);
        if ($category !== '' && !isset($clean[strtolower($category)])) $clean[strtolower($category)] = $category;
    }
    return array_values($clean);
}

/** New values are JSON arrays. Match full store names when reading legacy comma lists. */
function supplierCategories(?string $value, array $storeCategories = []): array {
    $value = trim((string)$value);
    if ($value === '') return [];
    $decoded = json_decode($value, true);
    if (is_array($decoded) && array_is_list($decoded)) return cleanSupplierCategories($decoded);
    $parts = array_map('trim', explode(',', $value));
    $known = [];
    foreach ($storeCategories as $category) $known[] = array_map('trim', explode(',', $category));
    usort($known, static fn($a, $b) => count($b) <=> count($a));
    $categories = [];
    for ($i = 0; $i < count($parts);) {
        $matched = false;
        foreach ($known as $tokens) {
            $slice = array_slice($parts, $i, count($tokens));
            if (array_map('strtolower', $slice) === array_map('strtolower', $tokens)) {
                $categories[] = implode(', ', $slice);
                $i += count($tokens);
                $matched = true;
                break;
            }
        }
        if (!$matched) $categories[] = $parts[$i++];
    }
    return cleanSupplierCategories($categories);
}

function supplierCategoriesForSave(array $input, array $storeCategories): ?string {
    $categories = isset($input['categories'])
        ? cleanSupplierCategories(is_array($input['categories']) ? $input['categories'] : [$input['categories']])
        : supplierCategories((string)($input['category'] ?? ''), $storeCategories);
    if (!$categories) return null;
    $json = json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    if (strlen($json) > 500) throw new InvalidArgumentException('Too many supplier categories. Please shorten the selection.');
    return $json;
}
