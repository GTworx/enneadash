<?php
/**
 * Dynamic Enneagram HTML Email Generator and Dispatcher
 * Using PDO database connections, custom type mapping, and template placeholder injection.
 */

// Start session if needed to fetch user_id securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Establish Secure PDO Database Connection
$dbHost = '127.0.0.1';
$dbPort = '3306';
$dbName = 'enneagram_app';
$dbUser = 'root';
$dbPass = 'pass123'; // Update configuration parameters based on environment

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database Connection Failure: " . htmlspecialchars($e->getMessage()));
}

// 2. Identify target user ID (Check CLI, Session, or GET parameters securely)
$userId = null;
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $userId = (int)$argv[1];
} elseif (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
} elseif (isset($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];
}

if (!$userId) {
    die("Error: No valid target User ID specified.");
}

// 3. Fetch User, Scores, and Responses Data using Prepared Statements
try {
    // A. Fetch User Profile
    $stmtUser = $pdo->prepare("SELECT u.id, COALESCE(p.name, 'Participant') as name, u.email_id as email FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id WHERE u.id = :user_id LIMIT 1");
    $stmtUser->execute([':user_id' => $userId]);
    $user = $stmtUser->fetch();
    if (!$user) {
        die("Error: User with ID {$userId} not found.");
    }

    // B. Fetch Enneagram Scores from enneagram_reports table
    $stmtScores = $pdo->prepare("SELECT raw_scores, enneagram_type as dominant_type, wing_1 as dominant_wing FROM enneagram_reports WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1");
    $stmtScores->execute([':user_id' => $userId]);
    $reportRow = $stmtScores->fetch();
    if (!$reportRow) {
        die("Error: Enneagram scores for User ID {$userId} not found.");
    }
    
    $rawScoresArr = json_decode($reportRow['raw_scores'], true);
    $scores = [];
    $scores['dominant_type'] = $reportRow['dominant_type'];
    $scores['dominant_wing'] = $reportRow['dominant_wing'];
    for ($t = 1; $t <= 9; $t++) {
        $scoreKey = "type_{$t}_score";
        $scores[$scoreKey] = $rawScoresArr[$t] ?? ($rawScoresArr[(string)$t] ?? 0.0);
    }

} catch (PDOException $e) {
    die("Database Query Error: " . htmlspecialchars($e->getMessage()));
}

// 4. Enneagram Psychological Profile Mapping
$enneagramTypes = [
    1 => [
        "name" => "The Perfectionist",
        "core_orientation" => "Doing the right thing, maintaining integrity and high standards.",
        "description" => "Perfectionists are rational, idealistic, principled, orderly, and self-controlled. Striving for perfection, they can be critical of themselves and others, always looking for improvement.",
        "key_traits" => "Ethical, organized, structured, conscientious, self-correcting.",
        "key_drivers" => "Being objective, accurate, improving oneself, and living with integrity.",
        "biggest_fear" => "Being corrupt, flawed, evil, or physically/morally defective.",
        "core_values" => "Integrity, excellence, responsibility, truthfulness, order.",
        "decision_making_style" => "Structured and rational, relying on rules, ethical guidelines, and analytical correctness.",
        "stress_reactions" => "Becomes moody, critical, and resentful under pressure (moves to Type 4).",
        "security_triggers" => "Becomes more spontaneous, relaxed, and creative when safe (moves to Type 7).",
        "core_fear" => "Being bad, corrupt, or wrong.",
        "core_desire" => "To be good, to have integrity, and to be balanced.",
        "core_weakness" => "Anger (resentment that is constantly repressed into self-control).",
        "soul_message" => "You are good as you are.",
        "growth_arrow_desc" => "In integration (growth), the Perfectionist moves towards Type 7, embracing spontaneity, joy, and lightheartedness.",
        "stress_arrow_desc" => "In disintegration (stress), the Perfectionist moves towards Type 4, experiencing feelings of alienation, self-pity, and moodiness.",
        "growth_action" => "Practice self-compassion and learn to accept imperfections as valuable parts of human growth.",
        "relationship_action" => "Avoid holding partners to impossible standards; express appreciation for their efforts.",
        "career_action" => "Delegate tasks confidently and avoid micromanaging project details.",
        "stress_action" => "Take deep breaths, allow yourself to play, and schedule guilt-free downtime weekly.",
        "daily_habit" => "Consciously pause once a day to notice something that is perfectly fine just as it is."
    ],
    2 => [
        "name" => "The Caregiver",
        "core_orientation" => "Expressing warmth, offering help, and cultivating affection.",
        "description" => "Caregivers are demonstrative, generous, people-pleasing, and possessive. They sincerely want to feel loved, useful, and appreciated, occasionally neglecting their own boundaries.",
        "key_traits" => "Empathetic, nurturing, warm, supportive, altruistic.",
        "key_drivers" => "Connecting with others, feeling needed, expressing affection, and defending the vulnerable.",
        "biggest_fear" => "Being unwanted, unworthy of love, or completely discarded.",
        "core_values" => "Unconditional love, generosity, relationships, service, compassion.",
        "decision_making_style" => "Relationship-centric, prioritizing the emotional impacts and needs of classmates or colleagues.",
        "stress_reactions" => "Becomes aggressive, demanding, and overly critical under pressure (moves to Type 8).",
        "security_triggers" => "Becomes self-reflective, creative, and introspective when secure (moves to Type 4).",
        "core_fear" => "Being unloved or unwanted for who they are.",
        "core_desire" => "To feel loved and appreciated.",
        "core_weakness" => "Pride (denying their own needs while over-emphasizing their helpfulness to others).",
        "soul_message" => "You are wanted and worthy of love.",
        "growth_arrow_desc" => "In growth, the Helper/Caregiver integrates towards Type 4, developing healthy self-care, creative expression, and authentic feelings.",
        "stress_arrow_desc" => "In stress, the Helper/Caregiver disintegrates towards Type 8, becoming controlling, confrontational, and demanding.",
        "growth_action" => "Set clear boundaries and practice saying 'no' when you are emotionally exhausted.",
        "relationship_action" => "Express your personal needs directly instead of expecting others to read your mind.",
        "career_action" => "Focus on your assigned job scope instead of taking on others' workloads out of obligation.",
        "stress_action" => "Step back, enjoy moments of isolation, and recharge through introspective creative activities.",
        "daily_habit" => "Write down three personal needs you have today and meet at least one of them."
    ],
    3 => [
        "name" => "The Performer",
        "core_orientation" => "Striving for success, outstanding achievements, and efficiency.",
        "description" => "Performers (or Achievers) are adaptable, ambitious, driven, and highly image-conscious. They value productivity, competency, and achieving goals that bring validation.",
        "key_traits" => "Goal-oriented, self-assured, efficient, energetic, charismatic.",
        "key_drivers" => "Being admired, distinguishing themselves, earning prestige, and avoiding failure.",
        "biggest_fear" => "Being worthless, incompetent, ineffective, or a failure.",
        "core_values" => "Success, productivity, distinction, competence, professional excellence.",
        "decision_making_style" => "Pragmatic, logical, and fast-paced, focusing entirely on execution and results.",
        "stress_reactions" => "Becomes disengaged, passive-aggressive, or sluggish under stress (moves to Type 9).",
        "security_triggers" => "Becomes cooperative, loyal, and community-minded when safe (moves to Type 6).",
        "core_fear" => "Being worthless or having no inherent value.",
        "core_desire" => "To feel valuable, successful, and respected.",
        "core_weakness" => "Deceit (crafting a successful image rather than showing their authentic self).",
        "soul_message" => "You are valued for who you are, not what you achieve.",
        "growth_arrow_desc" => "In growth, the Achiever integrates towards Type 6, becoming more cooperative, loyal, and committed to group well-being.",
        "stress_arrow_desc" => "In stress, the Achiever disintegrates towards Type 9, shutting down and becoming lethargic or directionless.",
        "growth_action" => "Value relationships and teamwork over individual metrics or social status.",
        "relationship_action" => "Share your failures and fears with trusted loved ones to cultivate authenticity.",
        "career_action" => "Balance hard work with strategic pauses; allow collaborators to take the lead occasionally.",
        "stress_action" => "Recognize when you are running on empty; disconnect from devices and sleep.",
        "daily_habit" => "Spend ten minutes reflecting on your day without measuring your productivity."
    ],
    4 => [
        "name" => "The Individualist",
        "core_orientation" => "Expressing authentic identity, expressing depth, and appreciating aesthetics.",
        "description" => "Individualists are expressive, dramatic, self-absorbed, and temperamental. They value authenticity and unique creative expression, seeking meaning in all aspects of life.",
        "key_traits" => "Intuitive, authentic, sensitive, expressive, introspective.",
        "key_drivers" => "Creating beauty, understanding deep emotions, staying true to oneself, and honoring feelings.",
        "biggest_fear" => "Having no unique identity or personal significance.",
        "core_values" => "Authenticity, aesthetic beauty, emotional depth, true individuality, self-expression.",
        "decision_making_style" => "Intuitive and emotional, strongly guided by how choices align with internal values.",
        "stress_reactions" => "Becomes clingy, dependent, and overly people-pleasing under pressure (moves to Type 2).",
        "security_triggers" => "Becomes objective, organized, and active when safe (moves to Type 1).",
        "core_fear" => "Having no identity or significance.",
        "core_desire" => "To cultivate a unique identity and find significance.",
        "core_weakness" => "Envy (feeling that everyone else possesses qualities they lack).",
        "soul_message" => "You are seen and appreciated for your unique beauty.",
        "growth_arrow_desc" => "In growth, the Individualist integrates towards Type 1, translating feelings into objective action, discipline, and order.",
        "stress_arrow_desc" => "In stress, the Individualist disintegrates towards Type 2, seeking validation and becoming overly dependent on others.",
        "growth_action" => "Build healthy routines and structures to ground your complex emotional world.",
        "relationship_action" => "Avoid getting caught in cycles of pull-and-push dynamics; appreciate stable, quiet affection.",
        "career_action" => "Commit to completing projects even when your creative inspiration temporarily fades.",
        "stress_action" => "Channel intense emotions into structured journaling, exercising, or volunteering.",
        "daily_habit" => "Focus on active tasks and execute one objective chore first thing each morning."
    ],
    5 => [
        "name" => "The Investigator",
        "core_orientation" => "Acquiring knowledge, understanding mechanisms, and protecting energy.",
        "description" => "Investigators are perceptive, innovative, secretive, and detached. They specialize in deep analysis, requiring quiet independence and mental clarity to recharge.",
        "key_traits" => "Analytical, insightful, independent, private, conceptual.",
        "key_drivers" => "Obtaining mastery, processing facts, maintaining autonomy, and escaping emotional noise.",
        "biggest_fear" => "Being overwhelmed, helpless, incapable, or ignorant.",
        "core_values" => "Mastery, rationality, independence, deep knowledge, clarity.",
        "decision_making_style" => "Highly objective, data-driven, and systematic, minimizing emotional interference.",
        "stress_reactions" => "Becomes hyperactive, distracted, and scattered under stress (moves to Type 7).",
        "security_triggers" => "Becomes self-assured, assertive, and physically active when safe (moves to Type 8).",
        "core_fear" => "Being useless, helpless, or incapable.",
        "core_desire" => "To be capable, competent, and fully knowledgeable.",
        "core_weakness" => "Avarice (hoarding info, time, and emotional energy to avoid dependency).",
        "soul_message" => "Your presence is capable and welcome in this world.",
        "growth_arrow_desc" => "In growth, the Investigator integrates towards Type 8, stepping into leadership and assertive, confident physical action.",
        "stress_arrow_desc" => "In stress, the Investigator disintegrates towards Type 7, escaping into theory, distraction, or frantic mental rabbit holes.",
        "growth_action" => "Share your thoughts early and step out of isolation to collaborate in physical groups.",
        "relationship_action" => "Practice sharing your emotional states directly rather than withdrawing into protective silence.",
        "career_action" => "Trust your competence and launch projects before you feel 100% prepared.",
        "stress_action" => "Engage your body through physical exercise to pull energy down from your head.",
        "daily_habit" => "Have a brief, casual conversation with someone about something unrelated to work."
    ],
    6 => [
        "name" => "The Loyalist",
        "core_orientation" => "Ensuring safety, maintaining trust, and building secure alliances.",
        "description" => "Loyalists are engaging, responsible, anxious, and suspicious. They seek stable guidance, support systems, and consistency to alleviate underlying anxiety.",
        "key_traits" => "Reliable, committed, alert, trustworthy, collaborative.",
        "key_drivers" => "Belonging to a trusted group, anticipating hazards, obtaining safety, and defending policies.",
        "biggest_fear" => "Being without support, guidance, or security; being abandoned.",
        "core_values" => "Trustworthiness, security, community loyalty, preparation, responsibility.",
        "decision_making_style" => "Collaborative and risk-averse, consulting trust systems and planning contingencies.",
        "stress_reactions" => "Becomes competitive, image-conscious, and workaholic under stress (moves to Type 3).",
        "security_triggers" => "Becomes relaxed, optimistic, and experimental when safe (moves to Type 9).",
        "core_fear" => "Being unsupported, guide-less, or abandoned.",
        "core_desire" => "To have security and support.",
        "core_weakness" => "Fear (continually planning for the worst possibilities to preempt anxiety).",
        "soul_message" => "You are safe, supported, and guided.",
        "growth_arrow_desc" => "In growth, the Loyalist integrates towards Type 9, finding inner calm, trusting life, and letting go of constant scanning.",
        "stress_arrow_desc" => "In stress, the Loyalist disintegrates towards Type 3, acting driven, defensive, and projecting a false, competent mask.",
        "growth_action" => "Develop confidence in your own authority and trust your primary instincts.",
        "relationship_action" => "Avoid testing your partner's loyalty; express your vulnerabilities openly instead.",
        "career_action" => "Acknowledge progress and success instead of focusing only on what could go wrong.",
        "stress_action" => "Limit news intake and practice mindfulness techniques to quiet catastrophic loops.",
        "daily_habit" => "Identify one situation today where you can trust the natural flow of outcomes."
    ],
    7 => [
        "name" => "The Enthusiast",
        "core_orientation" => "Seeking excitement, options, versatility, and avoiding discomfort.",
        "description" => "Enthusiasts are spontaneous, versatile, distractible, and quick-thinking. They seek positive experiences, constantly planning future options to outrun inner pain.",
        "key_traits" => "Optimistic, playful, quick-witted, adventurous, versatile.",
        "key_drivers" => "Staying stimulated, keeping options open, experiencing pleasure, and avoiding boredom/sorrow.",
        "biggest_fear" => "Being deprived, pain-bound, trapped in negativity, or limited.",
        "core_values" => "Freedom, joy, optimism, abundance, lifelong learning.",
        "decision_making_style" => "Fast and expansive, prioritizing possibilities, novel ideas, and positive opportunities.",
        "stress_reactions" => "Becomes critical, perfectionistic, and demanding under stress (moves to Type 1).",
        "security_triggers" => "Becomes focused, quiet, and deeply analytical when safe (moves to Type 5).",
        "core_fear" => "Being deprived, trapped, or stuck in pain.",
        "core_desire" => "To be free, happy, and fully satisfied.",
        "core_weakness" => "Gluttony (insatiable craving for future plans and fresh, exciting stimulations).",
        "soul_message" => "You will be completely provided for.",
        "growth_arrow_desc" => "In growth, the Enthusiast integrates towards Type 5, developing focus, deep analytical capacity, and calm patience.",
        "stress_arrow_desc" => "In stress, the Enthusiast disintegrates towards Type 1, becoming dogmatic, irritable, and structural.",
        "growth_action" => "Practice staying in the present moment, even when experiencing mild discomfort or boredom.",
        "relationship_action" => "Commit to deep, serious conversations and showing up during difficult emotional seasons.",
        "career_action" => "See projects through to completion before launching into the next attractive idea.",
        "stress_action" => "Slow down your speech, schedule moments of silence, and restrict multitasking.",
        "daily_habit" => "Stay with a simple, quiet task for twenty consecutive minutes without checking your phone."
    ],
    8 => [
        "name" => "The Challenger",
        "core_orientation" => "Expressing strength, asserting control, and protecting resources.",
        "description" => "Challengers are self-confident, strong, assertive, and protective. They stand up for beliefs, resist manipulation, and guard their personal vulnerabilities.",
        "key_traits" => "Direct, protective, decisive, powerful, truth-seeking.",
        "key_drivers" => "Being self-reliant, protecting their inner circle, dominating spaces, and staying strong.",
        "biggest_fear" => "Being controlled, harmed, weak, or dependent on others.",
        "core_values" => "Strength, justice, honesty, control, self-reliance.",
        "decision_making_style" => "Decisive and action-oriented, preferring intuitive, swift execution that demonstrates leadership.",
        "stress_reactions" => "Becomes quiet, withdrawn, and hyper-observant under pressure (moves to Type 5).",
        "security_triggers" => "Becomes open-hearted, caring, and protective of others when safe (moves to Type 2).",
        "core_fear" => "Being controlled, harmed, or vulnerable.",
        "core_desire" => "To protect themselves and determine their own path.",
        "core_weakness" => "Lust (intensity of force, desire to dominate and possess life experiences).",
        "soul_message" => "You will not be harmed; it is safe to open your heart.",
        "growth_arrow_desc" => "In growth, the Challenger integrates towards Type 2, displaying gentle care, empathy, and open-hearted vulnerability.",
        "stress_arrow_desc" => "In stress, the Challenger disintegrates towards Type 5, withdrawing, hoarding energy, and analyzing threat vectors.",
        "growth_action" => "Practice letting down your defenses and trusting others with your personal vulnerabilities.",
        "relationship_action" => "Soften your style of communication and listen actively without planning a counterargument.",
        "career_action" => "Encourage others to lead and build consensus rather than directing by sheer force of will.",
        "stress_action" => "Recognize when anger is masking fatigue, and check in with your quiet feelings.",
        "daily_habit" => "Consciously cede control over a small daily choice (such as choosing a restaurant) to someone else."
    ],
    9 => [
        "name" => "The Peacemaker",
        "core_orientation" => "Maintaining inner calm, resolving conflicts, and adapting to others.",
        "description" => "Peacemakers are receptive, reassuring, agreeable, and complacent. They avoid conflict to maintain peace, occasionally minimizing their own views.",
        "key_traits" => "Easygoing, harmonious, accommodating, patient, diplomatic.",
        "key_drivers" => "Maintaining peace, avoiding tension, holding stability, and uniting groups.",
        "biggest_fear" => "Fragmentation, separation, conflict, being overlooked, or cut off.",
        "core_values" => "Harmony, peace of mind, stability, inclusivity, patience.",
        "decision_making_style" => "Deliberate and consensus-driven, striving to make sure all perspectives feel valued.",
        "stress_reactions" => "Becomes anxious, reactive, and hyper-vigilant under pressure (moves to Type 6).",
        "security_triggers" => "Becomes highly focused, efficient, and self-developing when safe (moves to Type 3).",
        "core_fear" => "Loss of connection, conflict, and separation.",
        "core_desire" => "To have inner stability and peace of mind.",
        "core_weakness" => "Sloth (unwillingness to show presence and assert personal desires).",
        "soul_message" => "Your presence matters in this world.",
        "growth_arrow_desc" => "In growth, the Peacemaker integrates towards Type 3, taking proactive steps, asserting presence, and achieving goals.",
        "stress_arrow_desc" => "In stress, the Peacemaker disintegrates towards Type 6, becoming anxious, suspicious, and hyper-planning.",
        "growth_action" => "Acknowledge your own anger as a source of energy, and express your opinions directly.",
        "relationship_action" => "Avoid saying 'yes' when you want to say 'no'; hold space for healthy friction.",
        "career_action" => "Prioritize your own actions first and speak up in meetings to share your insights.",
        "stress_action" => "Identify chores you have been postponing and execute one immediately.",
        "daily_habit" => "Speak up clearly and share your choice when asked 'What do you want to do?'"
    ]
];

// Determine Dominant Type metadata
$dominantType = (int)($scores['dominant_type'] ?? 9);
$dominantWing = (int)($scores['dominant_wing'] ?? 1);
$matchPercentage = (float)($scores['match_percentage'] ?? 0.0);

$domMeta = $enneagramTypes[$dominantType] ?? $enneagramTypes[9]; // Fallback to 9
$wingMeta = $enneagramTypes[$dominantWing] ?? $enneagramTypes[1];

// Define archetype names (Standard Enneagram descriptions)
$wingArchetypes = [
    "1w9" => "The Idealist", "1w2" => "The Activist",
    "2w1" => "The Companion", "2w3" => "The Host/Hostess",
    "3w2" => "The Star", "3w4" => "The Professional",
    "4w3" => "The Aristocrat", "4w5" => "The Bohemian",
    "5w4" => "The Iconoclast", "5w6" => "The Troubleshooter",
    "6w5" => "The Defender", "6w7" => "The Buddy",
    "7w6" => "The Pathfinder", "7w8" => "The Realist",
    "8w7" => "The Independent", "8w9" => "The Bear",
    "9w8" => "The Referee", "9w1" => "The Dreamer"
];

$wingKey = "{$dominantType}w{$dominantWing}";
$wingArchetypeName = $wingArchetypes[$wingKey] ?? "The Helper";
$wingDescriptionText = "Your dominant score is driven by Type {$dominantType}, with a strong secondary influence from your wing, Type {$dominantWing}. This creates the unique personality archetype known as '{$wingArchetypeName}'. This blend guides how you navigate challenges, balancing the core drives of {$domMeta['name']} with the traits of {$wingMeta['name']}.";

// Set media URLs securely (Can be local paths, assets, or global images)
$diagramUrl = "https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?q=80&w=600&auto=format&fit=crop"; // Placeholder diagram
$wingsUrl = "https://images.unsplash.com/photo-1557683316-973673baf926?q=80&w=400&auto=format&fit=crop"; // Premium background

// 5. Load and Dynamically Populate template
$templatePath = __DIR__ . '/test.html';
if (!file_exists($templatePath)) {
    die("Error: The template file '{$templatePath}' is missing.");
}

$emailBody = file_get_contents($templatePath);

// Determine or construct BASE URL if executing via web context or generate fallback
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$subFolder = rtrim(dirname($scriptName), '/\\');
$baseUrl = "{$proto}://{$host}" . $subFolder;

// Fetch logo URL from mysql images table if available
$logoUrl = "https://raw.githubusercontent.com/Garima2019/enneadash_voice1/main/logo.jpg";
try {
    $stmtLogo = $pdo->prepare("SELECT image_url FROM images WHERE file_name = 'logo.jpg' LIMIT 1");
    $stmtLogo->execute();
    $logoRow = $stmtLogo->fetch();
    if ($logoRow && !empty($logoRow['image_url'])) {
        $logoUrl = $logoRow['image_url'];
    }
} catch (Throwable $e) {
    // Fall back to github URL if error
}

// Create mappings array for variables
$placeholders = [
    '{{user_name}}' => htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'),
    '{{logo_url}}' => htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'),
    '{{dominant_type_diagram_url}}' => htmlspecialchars($diagramUrl, ENT_QUOTES, 'UTF-8'),
    '{{dominant_type}}' => "Type {$dominantType} - " . $domMeta['name'],
    '{{core_orientation}}' => htmlspecialchars($domMeta['core_orientation'], ENT_QUOTES, 'UTF-8'),
    '{{dominant_type_description}}' => htmlspecialchars($domMeta['description'], ENT_QUOTES, 'UTF-8'),
    '{{key_traits}}' => htmlspecialchars($domMeta['key_traits'], ENT_QUOTES, 'UTF-8'),
    '{{key_drivers}}' => htmlspecialchars($domMeta['key_drivers'], ENT_QUOTES, 'UTF-8'),
    '{{biggest_fear}}' => htmlspecialchars($domMeta['biggest_fear'], ENT_QUOTES, 'UTF-8'),
    '{{core_values}}' => htmlspecialchars($domMeta['core_values'], ENT_QUOTES, 'UTF-8'),
    '{{decision_making_style}}' => htmlspecialchars($domMeta['decision_making_style'], ENT_QUOTES, 'UTF-8'),
    '{{stress_reactions}}' => htmlspecialchars($domMeta['stress_reactions'], ENT_QUOTES, 'UTF-8'),
    '{{security_triggers}}' => htmlspecialchars($domMeta['security_triggers'], ENT_QUOTES, 'UTF-8'),
    '{{core_fear}}' => htmlspecialchars($domMeta['core_fear'], ENT_QUOTES, 'UTF-8'),
    '{{core_desire}}' => htmlspecialchars($domMeta['core_desire'], ENT_QUOTES, 'UTF-8'),
    '{{core_weakness}}' => htmlspecialchars($domMeta['core_weakness'], ENT_QUOTES, 'UTF-8'),
    '{{soul_message}}' => htmlspecialchars($domMeta['soul_message'], ENT_QUOTES, 'UTF-8'),
    '{{growth_arrow_desc}}' => htmlspecialchars($domMeta['growth_arrow_desc'], ENT_QUOTES, 'UTF-8'),
    '{{stress_arrow_desc}}' => htmlspecialchars($domMeta['stress_arrow_desc'], ENT_QUOTES, 'UTF-8'),
    '{{wings_image_url}}' => htmlspecialchars($wingsUrl, ENT_QUOTES, 'UTF-8'),
    '{{wing_archetype}}' => htmlspecialchars($wingArchetypeName, ENT_QUOTES, 'UTF-8'),
    '{{wing_description}}' => htmlspecialchars($wingDescriptionText, ENT_QUOTES, 'UTF-8'),
    '{{growth_action}}' => htmlspecialchars($domMeta['growth_action'], ENT_QUOTES, 'UTF-8'),
    '{{relationship_action}}' => htmlspecialchars($domMeta['relationship_action'], ENT_QUOTES, 'UTF-8'),
    '{{career_action}}' => htmlspecialchars($domMeta['career_action'], ENT_QUOTES, 'UTF-8'),
    '{{stress_action}}' => htmlspecialchars($domMeta['stress_action'], ENT_QUOTES, 'UTF-8'),
    '{{daily_habit}}' => htmlspecialchars($domMeta['daily_habit'], ENT_QUOTES, 'UTF-8')
];

// Perform straightforward key replacements
foreach ($placeholders as $placeholder => $value) {
    $emailBody = str_replace($placeholder, $value, $emailBody);
}

// 6. Programmatically handle the score breakdown table
$scoreRowsHtml = "";
for ($t = 1; $t <= 9; $t++) {
    $scoreKey = "type_{$t}_score";
    $rawScore = isset($scores[$scoreKey]) ? (float)$scores[$scoreKey] : 0.0;
    
    // Scale normalized rating (0-5 scale)
    $normalizedRating = number_format(($rawScore / 100.0) * 5.0, 2);
    $percentage = number_format($rawScore, 1);
    
    $typeName = "Type {$t} - " . ($enneagramTypes[$t]['name'] ?? 'Unknown');
    if ($t === $dominantType) {
        $typeName .= " <strong>(Dominant)</strong>";
    }

    $scoreRowsHtml .= "<tr style=\"border-bottom: 1px solid #f1f5f9;\">\n";
    $scoreRowsHtml .= "    <td style=\"padding: 10px; color: #334155; font-weight: 500;\">{$typeName}</td>\n";
    $scoreRowsHtml .= "    <td align=\"center\" style=\"padding: 10px; color: #64748b;\">{$normalizedRating} / 5.00</td>\n";
    $scoreRowsHtml .= "    <td align=\"right\" style=\"padding: 10px; color: #0f172a; font-weight: 600;\">{$percentage}%</td>\n";
    $scoreRowsHtml .= "</tr>\n";
}

// Locate and replace score breakdown block
$scorePattern = '/\{\{#each score_breakdown\}\}.*?\{\{\/each\}\}/s';
$emailBody = preg_replace($scorePattern, $scoreRowsHtml, $emailBody);


// 8. Output or Send Email Execution Block
$recipientEmail = filter_var($user['email'], FILTER_VALIDATE_EMAIL);
$recipientName = strip_tags($user['name']);
$subject = "Your EnneaDash Report - Type {$dominantType} - " . $domMeta['name'];

if (!$recipientEmail) {
    die("Error: User email address is invalid.");
}

// Standard Native mail() implementation outline
// To test this or run it, uncomment the mail execution section below:
/*
$headers = [
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/html; charset=utf-8',
    'From' => 'no-reply@enneascope.com',
    'Reply-To' => 'support@enneascope.com',
    'X-Mailer' => 'PHP/' . phpversion()
];

$mailSent = mail($recipientEmail, $subject, $emailBody, $headers);
if ($mailSent) {
    echo "Success: HTML email dispatched successfully to {$recipientEmail}.\n";
} else {
    echo "Error: Native mail delivery failed.\n";
}
*/

// Modern PHPMailer Integration Outline (SMTP Security Compliant)
/*
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = 0;                                       // Disable verbose debug output
    $mail->isSMTP();                                            // Set mailer to use SMTP
    $mail->Host       = 'smtp.mailtrap.io';                     // Specify main and backup SMTP servers
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = 'your_smtp_username';                   // SMTP username
    $mail->Password   = 'your_smtp_password';                   // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption, `ssl` also accepted
    $mail->Port       = 587;                                    // TCP port to connect to

    // Recipients
    $mail->setFrom('assessments@enneascope.com', 'EnneaScope App');
    $mail->addAddress($recipientEmail, $recipientName);         // Add a recipient

    // Content
    $mail->isHTML(true);                                        // Set email format to HTML
    $mail->Subject = $subject;
    $mail->Body    = $emailBody;
    $mail->AltBody = strip_tags(html_entity_decode($emailBody)); // Plain text version of email for compatibility

    $mail->send();
    echo "Success: PHPMailer dispatched SMTP email securely to {$recipientEmail}.\n";
} catch (Exception $e) {
    echo "Error: Email message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
}
*/

// For demonstration/CLI purposes, print the completion notice
echo "Success: Email body parsed successfully for {$recipientName} ({$recipientEmail}).\n";
echo "Character Count of generated email body: " . strlen($emailBody) . " characters.\n";
// Save processed body to a test output file to let the user review it
file_put_contents(__DIR__ . '/test_email_output.html', $emailBody);
echo "Preview output saved to " . __DIR__ . "/test_email_output.html\n";
?>
