<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
$userRole = $input['user_role'] ?? 'guest';
$timestamp = $input['timestamp'] ?? time();

if ($message === '') {
    echo json_encode([
        'reply' => 'Please type your question.',
        'suggestions' => ['Help', 'How to export attendance?', 'Manage teachers']
    ]);
    exit();
}

// Load responses
$responsesFile = __DIR__ . '/../data/chat_responses.json';
$default = "I didn't understand that. Try: 'help', 'export', 'manage teachers', 'qr'.";

if (!file_exists($responsesFile)) {
    echo json_encode([
        'reply' => $default,
        'suggestions' => ['Help', 'Export attendance', 'Manage teachers']
    ]);
    exit();
}

$data = json_decode(file_get_contents($responsesFile), true);
$lc = strtolower($message);
$reply = null;
$confidence = 0;
$matchedKeywords = [];

// Enhanced keyword matching with scoring
foreach ($data['responses'] as $r) {
    $score = 0;
    $matched = [];

    foreach ($r['keywords'] as $kw) {
        if ($kw !== '' && strpos($lc, strtolower($kw)) !== false) {
            $score += strlen($kw); // Longer matches get higher score
            $matched[] = $kw;
        }
    }

    // Boost score for exact matches
    if (in_array($lc, array_map('strtolower', $r['keywords']))) {
        $score *= 2;
    }

    if ($score > $confidence) {
        $confidence = $score;
        $reply = $r['reply'];
        $matchedKeywords = $matched;
    }
}

// Role-based responses
if ($userRole === 'admin' && strpos($lc, 'manage') !== false) {
    $reply = "As an admin, you can manage teachers, classes, and view all attendance records. Go to Admin Dashboard → Manage Teachers or Manage Classes.";
} elseif ($userRole === 'teacher' && strpos($lc, 'qr') !== false) {
    $reply = "As a teacher, go to Teacher Dashboard → QR Code to view your personal QR code for attendance scanning.";
} elseif ($userRole === 'class_rep' && strpos($lc, 'scan') !== false) {
    $reply = "As a class representative, go to Class Rep Dashboard → Scan QR to mark attendance for teachers.";
}

// Contextual responses
if (strpos($lc, 'thank') !== false || strpos($lc, 'thanks') !== false) {
    $reply = "You're welcome! 😊 Is there anything else I can help you with?";
} elseif (strpos($lc, 'bye') !== false || strpos($lc, 'goodbye') !== false) {
    $reply = "Goodbye! 👋 Feel free to chat anytime you need help with the attendance system.";
} elseif (strpos($lc, 'problem') !== false || strpos($lc, 'issue') !== false || strpos($lc, 'error') !== false) {
    $reply = "I'm sorry you're experiencing issues. Try refreshing the page, or contact your system administrator if problems persist.";
}

// Fallback
if ($reply === null) {
    $reply = $data['default'] ?? $default;
}

// Generate contextual suggestions
$suggestions = generateSuggestions($message, $userRole, $matchedKeywords);

// Add emoji and formatting
$reply = enhanceReply($reply, $userRole);

// Log interaction
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$logEntry = sprintf(
    "%s\t%s\t%s\t%s\t%s\t%s\n",
    date('Y-m-d H:i:s'),
    $userRole,
    substr($message, 0, 100),
    substr($reply, 0, 200),
    implode(',', $matchedKeywords),
    $confidence
);
@file_put_contents($logDir . '/chat.log', $logEntry, FILE_APPEND);

echo json_encode([
    'reply' => $reply,
    'suggestions' => $suggestions,
    'confidence' => $confidence,
    'matched_keywords' => $matchedKeywords,
    'timestamp' => time()
]);

function generateSuggestions($message, $userRole, $matchedKeywords) {
    $baseSuggestions = ['Help', 'How to export attendance?', 'Manage teachers'];

    // Role-specific suggestions
    $roleSuggestions = [
        'admin' => ['View statistics', 'Manage classes', 'Export reports'],
        'teacher' => ['View schedule', 'Update profile', 'QR code'],
        'class_rep' => ['Scan QR codes', 'View attendance', 'Mark attendance']
    ];

    $suggestions = $roleSuggestions[$userRole] ?? $baseSuggestions;

    // Context-aware suggestions
    $lc = strtolower($message);
    if (strpos($lc, 'export') !== false) {
        $suggestions = ['Export attendance', 'Filter by date', 'Download CSV'];
    } elseif (strpos($lc, 'manage') !== false) {
        $suggestions = ['Add teacher', 'Edit class', 'View statistics'];
    } elseif (strpos($lc, 'qr') !== false) {
        $suggestions = ['Generate QR', 'Scan QR', 'QR tutorial'];
    } elseif (strpos($lc, 'help') !== false) {
        $suggestions = ['Getting started', 'Troubleshooting', 'Contact support'];
    }

    return array_slice($suggestions, 0, 3);
}

function enhanceReply($reply, $userRole) {
    // Add role-specific context
    if ($userRole === 'admin') {
        $reply = str_replace('Go to', "As admin, go to", $reply);
    } elseif ($userRole === 'teacher') {
        $reply = str_replace('Go to', "As teacher, go to", $reply);
    } elseif ($userRole === 'class_rep') {
        $reply = str_replace('Go to', "As class rep, go to", $reply);
    }

    // Add helpful emojis
    $emojiMap = [
        'export' => '📊',
        'manage' => '⚙️',
        'qr' => '📱',
        'help' => '❓',
        'error' => '⚠️',
        'success' => '✅',
        'teacher' => '👨‍🏫',
        'class' => '🏫',
        'attendance' => '📝'
    ];

    foreach ($emojiMap as $keyword => $emoji) {
        if (strpos(strtolower($reply), strtolower($keyword)) !== false) {
            $reply = $emoji . ' ' . $reply;
            break;
        }
    }

    return $reply;
}
?>
