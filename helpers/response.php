<?php

function jsonResponse(int $statusCode, array $data): void
{
    if (!headers_sent()) {
        http_response_code($statusCode);
        header("Content-Type: application/json; charset=UTF-8");
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
?>