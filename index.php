<?php

$dbHost = 'localhost';
$dbName = 'converter';
$dbUser = 'root';
$dbPass = '';

try {
    // database connection
    $conn = new PDO('mysql:host='.$dbHost.';dbname='.$dbName, $dbUser, $dbPass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database Connected successfully<br>";
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

try {
    $csvFile = 'MOCK_DATA.csv';

    // open and read CSV file
    if (($handle = fopen($csvFile, 'r')) !== false) {
        $headers = fgetcsv($handle, 1000, ',');

        // number of records for each batch
        $batchSize = 50;
        $batchCount = 0;
        $batchData = [];

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $rowData = array_combine($headers, $row);

            if (isset($rowData["ApplicantCardStatus"]) && $rowData["ApplicantCardStatus"] === "Active") {
                $filteredData = [];
                $filteredData["hash"] = strval(uniqid());
                $filteredData["title"] = isset($rowData["Title"]) && !empty($rowData["Title"]) ? $rowData["Title"] : null;
                $filteredData["first_name"] = isset($rowData["Name"]) && !empty($rowData["Name"]) ? explode(' ', $rowData["Name"])[0] : null;
                $filteredData["last_name"] = (isset($rowData["Name"]) && !empty($rowData["Name"]) && strpos($rowData["Name"], ' ') !== false) ? explode(' ', $rowData["Name"])[1] : null;
                $filteredData["mobile"] = isset($rowData["mobile_phone_number"]) && !empty($rowData["mobile_phone_number"]) ? $rowData["mobile_phone_number"] : null;
                $filteredData["home_phone"] = isset($rowData["landline_phone_number"]) && !empty($rowData["landline_phone_number"]) ? $rowData["landline_phone_number"] : null;
                $filteredData["primary_email"] = isset($rowData["Email"]) && !empty($rowData["Email"]) ? $rowData["Email"] : '';
                $filteredData["secondary_email"] = isset($rowData["secondary_email"]) && !empty($rowData["secondary_email"]) ? $rowData["secondary_email"] : null;
                $filteredData["address_number"] = isset($rowData["street_address"]) && !empty($rowData["street_address"]) ? explode(' ', $rowData["street_address"])[0] : null;
                $filteredData["address_street"] = isset($rowData["street_address"]) && !empty($rowData["street_address"]) ? implode(' ', array_slice(explode(' ', $rowData["street_address"]), 1)) : null;
                $filteredData["min_price"] = isset($rowData["minimum_price"]) && !empty($rowData["minimum_price"]) ? floatval($rowData["minimum_price"]) : null;
                $filteredData["max_price"] = isset($rowData["maximum_price"]) && !empty($rowData["maximum_price"]) ? floatval($rowData["maximum_price"]) : null;

                $batchData[] = $filteredData;
                $batchCount++;

                // insert into batch when batch size is full
                if ($batchCount >= $batchSize) {
                    insertBatch($conn, $batchData);
                    $batchData = [];
                    $batchCount = 0;
                }
            }
        }

        // insert any remaining records in the last batch
        if (!empty($batchData)) {
            insertBatch($conn, $batchData);
        }

        fclose($handle);

        echo "All records inserted successfully<br>";

    } else {
        echo "Could not open the CSV file<br>";
    }

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

// Function to insert a batch of records into the database
function insertBatch($conn, $batchData) {
    try {
        $query = $conn->prepare("INSERT INTO applicant (`hash`,`title`, `first_name`, `last_name`, `mobile`, `home_phone`, `primary_email`, `secondary_email`, `address_number`, `address_street`, `min_price`, `max_price`)
            VALUES (:hash, :title, :first_name, :last_name, :mobile, :home_phone, :primary_email, :secondary_email, :address_number, :address_street, :min_price, :max_price)");

        $conn->beginTransaction();

        foreach ($batchData as $row) {
            $query->bindParam(':hash', $row['hash']);
            $query->bindParam(':title', $row['title']);
            $query->bindParam(':first_name', $row['first_name']);
            $query->bindParam(':last_name', $row['last_name']);
            $query->bindParam(':mobile', $row['mobile']);
            $query->bindParam(':home_phone', $row['home_phone']);
            $query->bindParam(':primary_email', $row['primary_email']);
            $query->bindParam(':secondary_email', $row['secondary_email']);
            $query->bindParam(':address_number', $row['address_number']);
            $query->bindParam(':address_street', $row['address_street']);
            $query->bindParam(':min_price', $row['min_price']);
            $query->bindParam(':max_price', $row['max_price']);
            $query->execute();
        }

        $conn->commit();
    } catch (PDOException $e) {
        $conn->rollBack();
        echo 'Error inserting batch: ' . $e->getMessage();
    }

}

?>