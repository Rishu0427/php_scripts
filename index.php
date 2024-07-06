<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Function to read and filter JSON data based on the specified criteria
function filterJsonData($jsonFilePath, $criteria, $page, $pageSize) {
    if (!file_exists($jsonFilePath) || !is_readable($jsonFilePath)) {
        return json_encode(["message" => "JSON file not found or not readable"]);
    }

    $jsonData = file_get_contents($jsonFilePath);
    $dataArray = json_decode($jsonData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return json_encode(["message" => "Error decoding JSON data"]);
    }

    // Filter the data based on criteria
    $filteredData = array_filter($dataArray, function($item) use ($criteria) {
        foreach ($criteria as $key => $value) {
            if (!empty($value) && strtolower($item[$key]) != strtolower($value)) {
                return false;
            }
        }
        return true;
    });

    // Determine disabled filter dimensions using only filtered data
    $disabledFilters = getDisabledFilters($dataArray, $filteredData);

    // Paginate the filtered data
    $totalItems = count($filteredData);
    $totalPages = ceil($totalItems / $pageSize);
    $offset = ($page - 1) * $pageSize;
    $paginatedData = array_slice(array_values($filteredData), $offset, $pageSize);

    return json_encode([
        'filteredData' => $paginatedData,
        'totalItems' => $totalItems,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'pageSize' => $pageSize,
        'disabledFilters' => $disabledFilters
    ]);
}

// Function to get disabled filter dimensions and available options from the filtered data
function getDisabledFilters($fullData, $filteredData) {
    $disabledFilters = [];
    $filterableColumns = ['Ausführung', 'Farbigkeit', 'Format', 'Material'];
    $additionalColumns = ['Papier', 'Grammatur', 'Oberfläche'];
    $availableOptions = [];

    // Collect all unique values for each additional column
    foreach ($additionalColumns as $column) {
        var_dump($column);
        $allValues = array_unique(array_column($fullData, $column));
        $filteredValues = array_unique(array_column($filteredData, $column));
        $disabledOptions = array_diff($allValues, $filteredValues);

        if (!empty($disabledOptions)) {
            $disabledFilters[$column] = $disabledOptions;
        }

        $availableOptions[$column] = $filteredValues;
    }

    return [
        'disabledFilters' => $disabledFilters,
        'availableOptions' => $availableOptions
    ];
}

// Read POST parameters and only consider specific ones for filtering
$criteria = [
    "Ausführung" => isset($_POST['Ausführung']) ? $_POST['Ausführung'] : '',
    "Farbigkeit" => isset($_POST['Farbigkeit']) ? $_POST['Farbigkeit'] : '',
    "Format" => isset($_POST['Format']) ? $_POST['Format'] : '',
    "Material" => isset($_POST['Material']) ? $_POST['Material'] : ''
];

$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$pageSize = isset($_POST['pageSize']) ? (int)$_POST['pageSize'] : 20;

// Specify the path to the JSON file
$jsonFilePath = "./products.json";

// Get the filtered data and disabled filters from the JSON file
$result = filterJsonData($jsonFilePath, $criteria, $page, $pageSize);

// Return the result
echo $result;
?>
