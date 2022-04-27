<?php

function returnJSON($data = [] || '', $status = true) {
    echo json_encode(['status' => $status, 'code' => $status ? 200 : 400, 'data' => $data]);
}