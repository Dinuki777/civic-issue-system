<?php

function sanitize($data)
{
    return htmlspecialchars(trim(strip_tags($data)), ENT_QUOTES, 'UTF-8');
}

function generateReferenceNumber()
{
    return 'CIVIC-' . strtoupper(uniqid());
}

function logAudit($conn, $user_id, $action, $entity_type, $entity_id = null, $details = null)
{
    $ip = $_SERVER['REMOTE_ADDR'];

    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address)
                            VALUES (?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "ississ",
        $user_id,
        $action,
        $entity_type,
        $entity_id,
        $details,
        $ip
    );

    $stmt->execute();
    $stmt->close();
}

function addNotification($conn, $user_id, $complaint_id, $message)
{
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, complaint_id, message)
                            VALUES (?, ?, ?)");

    $stmt->bind_param(
        "iis",
        $user_id,
        $complaint_id,
        $message
    );

    $stmt->execute();
    $stmt->close();
}

?>