<?php
$dsn='pgsql:host=ep-long-pond-agq9ju7z-pooler.c-2.eu-central-1.aws.neon.tech;port=5432;dbname=neondb;sslmode=require;channel_binding=require';
$user='neondb_owner';
$password='npg_5iRT4jmxCebI';
try {
    $pdo=new PDO($dsn,$user,$password,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
    $stmt=$pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='chat_messages' ORDER BY ordinal_position");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['column_name'] . "\n";
    }
} catch (Exception $e) {
    echo 'error: '.$e->getMessage();
}
