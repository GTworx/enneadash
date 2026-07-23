<?php
$host = '127.0.0.1';
$dbname = 'enneagram_app';
$username = 'root';
$password = 'pass123'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Clear existing questions
    $pdo->exec("DELETE FROM questions");
    
    $questions = [
        // ==========================================
        // AGE GROUP: 18-25
        // ==========================================
        ['I strive to establish perfect habits and study/work routines early in life.', 1, '18-25'],
        ['I feel highly self-critical when I don\'t meet my own academic or personal standards.', 1, '18-25'],
        
        ['I often go out of my way to help my friends, even if it interferes with my study/work.', 2, '18-25'],
        ['I want my peers to see me as a supportive and reliable confidant.', 2, '18-25'],
        
        ['I am highly motivated to build an impressive resume and achieve early career success.', 3, '18-25'],
        ['I feel defined by my achievements and worry about being seen as unsuccessful by my peers.', 3, '18-25'],
        ['I adapt my presentation and online persona to project a highly successful image.', 3, '18-25'],
        
        ['I feel a strong need to express my unique identity and stand out from my peers.', 4, '18-25'],
        ['I often feel misunderstood or different from people my age.', 4, '18-25'],
        
        ['I prefer spending hours researching niche topics alone rather than socializing in large groups.', 5, '18-25'],
        ['I feel the need to gain expert knowledge before I feel confident acting in the real world.', 5, '18-25'],
        
        ['I worry a lot about my future security, career path, and finding a stable group of friends.', 6, '18-25'],
        ['I look for guidance from mentors, teachers, or clear rules to make sure I am on the right track.', 6, '18-25'],
        ['I am very loyal to my close group of friends and support them through thick and thin.', 6, '18-25'],
        
        ['I am always looking for the next exciting social event, trip, or experience to avoid missing out.', 7, '18-25'],
        ['I hate feeling restricted by rules or routines and prefer to keep my future plans open.', 7, '18-25'],
        
        ['I am not afraid to challenge authority figures or stand up for my friends\' rights.', 8, '18-25'],
        ['I value my independence highly and resist any attempts by others to control my decisions.', 8, '18-25'],
        
        ['I would rather go along with my friends\' plans than cause an argument by voicing my preference.', 9, '18-25'],
        ['I seek peaceful, low-stress environments and try to keep tension at bay in my social circle.', 9, '18-25'],

        // ==========================================
        // AGE GROUP: 26-35
        // ==========================================
        ['I am highly organized and strive to maintain a perfect work-life balance and clean home.', 1, '26-35'],
        ['I get frustrated when colleagues or partners do not follow through with their responsibilities.', 1, '26-35'],
        
        ['I invest a lot of energy into supporting my partner, family, or close colleagues.', 2, '26-35'],
        ['I struggle to say \'no\' to requests for help from friends and coworkers.', 2, '26-35'],
        
        ['I am intensely focused on climbing the career ladder and reaching my professional goals.', 3, '26-35'],
        ['I feel a pressure to look successful, wealthy, or accomplished to my peers and family.', 3, '26-35'],
        ['I am willing to sacrifice personal time to achieve my career milestones.', 3, '26-35'],
        
        ['I want my lifestyle and home to reflect my deep personal style and authentic self.', 4, '26-35'],
        ['I often feel like I don\'t fit into standard societal expectations of career or marriage.', 4, '26-35'],
        
        ['I need significant alone time after a long work day to recharge my mental energy.', 5, '26-35'],
        ['I prefer to solve complex problems through deep analysis and independent research.', 5, '26-35'],
        
        ['I place high value on job security, stable income, and building a reliable safety net.', 6, '26-35'],
        ['I tend to anticipate potential obstacles or financial crises in my career and planning.', 6, '26-35'],
        ['I seek out reliable communities, partnerships, or organizations that I can trust.', 6, '26-35'],
        
        ['I actively plan vacations and hobbies to escape the monotony of daily work routines.', 7, '26-35'],
        ['I love brainstorming new business ideas or life paths rather than sticking to one track.', 7, '26-35'],
        
        ['I take charge in professional projects and feel confident leading teams or resolving conflicts.', 8, '26-35'],
        ['I refuse to let bosses or partners micromanage me or dictate my life decisions.', 8, '26-35'],
        
        ['I prioritize keeping peace at home and work, often accommodating others to avoid drama.', 9, '26-35'],
        ['I find it hard to assert my own desires when they conflict with my partner\'s or family\'s plans.', 9, '26-35'],

        // ==========================================
        // AGE GROUP: 36-45
        // ==========================================
        ['I hold myself to strict moral and ethical standards in my parenting, career, and community work.', 1, '36-45'],
        ['I feel a strong duty to correct inefficiencies and disorganization in my environment.', 1, '36-45'],
        
        ['I derive great fulfillment from nurturing and helping my family, children, or community members.', 2, '36-45'],
        ['I often neglect my own health or emotional needs to take care of those who depend on me.', 2, '36-45'],
        
        ['I measure my worth by how well I provide for my family and the status I have achieved in my field.', 3, '36-45'],
        ['I feel a continuous pressure to perform at my peak and avoid any signs of professional decline.', 3, '36-45'],
        ['I actively seek recognition and leadership roles in my community or workplace.', 3, '36-45'],
        
        ['I value deep, meaningful conversations over superficial social chit-chat.', 4, '36-45'],
        ['I search for a deeper meaning or calling in my life beyond routine daily tasks.', 4, '36-45'],
        
        ['I protect my personal space and limits, avoiding too many social or familial demands.', 5, '36-45'],
        ['I enjoy mastering complex skills or collecting specialized knowledge in my spare time.', 5, '36-45'],
        
        ['I am highly vigilant about protecting my family\'s health, safety, and financial future.', 6, '36-45'],
        ['I rely heavily on trusted networks, institutions, and long-term friendships for security.', 6, '36-45'],
        ['I frequently double-check plans and worry about unforeseen crises or health issues.', 6, '36-45'],
        
        ['I make sure my life is filled with fun activities, travel, and adventure to offset mid-life stresses.', 7, '36-45'],
        ['I find comfort in starting new creative projects or hobbies to keep my mind stimulated.', 7, '36-45'],
        
        ['I am a protective shield for my family and coworkers, standing up against unfair treatment.', 8, '36-45'],
        ['I demand directness and honesty from my partner, kids, and professional associates.', 8, '36-45'],
        
        ['I prefer to maintain a calm, quiet home environment and will minimize conflicts to keep the peace.', 9, '36-45'],
        ['I easily adapt to the needs of my children or partner, sometimes forgetting what I want.', 9, '36-45'],

        // ==========================================
        // AGE GROUP: 46-55
        // ==========================================
        ['I focus on mentoring the younger generation and passing down correct principles and ethics.', 1, '46-55'],
        ['I get annoyed when younger people disregard proven rules or act irresponsibly.', 1, '46-55'],
        
        ['I am the primary emotional support for my aging parents, grown children, or colleagues.', 2, '46-55'],
        ['I sometimes worry that people only value me for what I can do for them.', 2, '46-55'],
        
        ['I want to ensure my career achievements leave a lasting legacy in my organization or field.', 3, '46-55'],
        ['I focus on maintaining high productivity and staying relevant in a changing work environment.', 3, '46-55'],
        ['I take pride in the tangible successes and status I have accumulated over my career.', 3, '46-55'],
        
        ['I spend a lot of time reflecting on my past, my choices, and my unique path through life.', 4, '46-55'],
        ['I feel a poignant sense of longing for what might have been or for deeper connections.', 4, '46-55'],
        
        ['I cherish intellectual independence and prefer reading or quiet hobbies to busy social gatherings.', 5, '46-55'],
        ['I like to observe and analyze human behavior from a detached, objective perspective.', 5, '46-55'],
        
        ['I focus on consolidating my retirement savings and ensuring long-term healthcare security.', 6, '46-55'],
        ['I value loyalty and reliability in my friends and colleagues above all other traits.', 6, '46-55'],
        ['I worry about societal instability, economic shifts, and the well-being of my loved ones.', 6, '46-55'],
        
        ['I look forward to exploring new travel destinations and learning new hobbies in the coming years.', 7, '46-55'],
        ['I try to keep my outlook positive and optimistic, avoiding dwelling on aging or limitations.', 7, '46-55'],
        
        ['I feel confident in my authority and don\'t hesitate to speak my mind on important issues.', 8, '46-55'],
        ['I protect my autonomy fiercely and reject any attempts to sideline or control me.', 8, '46-55'],
        
        ['I prioritize inner peace and tranquility, letting go of old grudges and avoiding drama.', 9, '46-55'],
        ['I find it easy to mediate disagreements among family members or coworkers to keep harmony.', 9, '46-55'],

        // ==========================================
        // AGE GROUP: 56-65
        // ==========================================
        ['I strive to maintain order, integrity, and high standards in my daily routines and community.', 1, '56-65'],
        ['I believe it is essential to stand up for moral principles and do what is right, even in retirement.', 1, '56-65'],
        
        ['I find great joy in helping my grandchildren, family, or volunteering for community causes.', 2, '56-65'],
        ['I want to feel needed by my family and friends as I transition to a new stage of life.', 2, '56-65'],
        
        ['I look back on my life\'s achievements with pride and still enjoy setting goals for myself.', 3, '56-65'],
        ['I want to be remembered for my accomplishments and the value I created during my career.', 3, '56-65'],
        ['I stay active and engaged in projects to maintain a sense of competence and success.', 3, '56-65'],
        
        ['I embrace my unique life journey, recognizing the beauty in both my joys and struggles.', 4, '56-65'],
        ['I feel a deep emotional depth and appreciate art, music, or nature on a profound level.', 4, '56-65'],
        
        ['I spend much of my time reading, writing, or studying topics that fascinate me.', 5, '56-65'],
        ['I value my privacy and peace, keeping my energy levels carefully managed.', 5, '56-65'],
        
        ['I am cautious about my health, investments, and making sure my family is secure.', 6, '56-65'],
        ['I rely on established communities, long-term friendships, and structured routines for comfort.', 6, '56-65'],
        ['I worry about future health issues, loss of independence, or unexpected life changes.', 6, '56-65'],
        
        ['I am excited to spend my time traveling, enjoying leisure activities, and trying new things.', 7, '56-65'],
        ['I focus on the bright side of life and actively avoid thinking about aging or physical decline.', 7, '56-65'],
        
        ['I am protective of my family, legacy, and resources, and I stand firm in my opinions.', 8, '56-65'],
        ['I refuse to let age limit my independence or allow others to make decisions for me.', 8, '56-65'],
        
        ['I seek a quiet, peaceful life and prefer to let go of conflicts and strive for inner harmony.', 9, '56-65'],
        ['I am content to go with the flow of my family\'s plans and enjoy their company without friction.', 9, '56-65']
    ];

    $stmt = $pdo->prepare("INSERT INTO questions (prompt_text, target_type, age_group, created_at) VALUES (?, ?, ?, NOW())");
    foreach ($questions as $q) {
        $stmt->execute($q);
    }

    echo "Successfully seeded " . count($questions) . " questions (20 per age group) into the database.\n";
} catch (PDOException $e) {
    echo "Seed Error: " . $e->getMessage() . "\n";
    exit(1);
}
