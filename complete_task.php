<?php
require_once __DIR__ . '/db.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

$user_id = $_SESSION['user_id'];
$task_id = (int)($_POST['task_id'] ?? 0);
if (!$task_id) {
    echo json_encode(['success'=>false,'error'=>'invalid_task']);
    exit;
}

try {
    $pdo->beginTransaction();

    // lock the task by task_id
    $stmt = $pdo->prepare('SELECT task_id,team_id,title,points,completed_at FROM team_tasks WHERE task_id=? FOR UPDATE');
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();
    if (!$task) {
        $pdo->rollBack();
        echo json_encode(['success'=>false,'error'=>'not_found']); exit;
    }
    if ($task['completed_at'] !== null) {
        $pdo->rollBack();
        echo json_encode(['success'=>false,'error'=>'already_completed']); exit;
    }

    $team_id = (int)$task['team_id'];

    // check user belongs to team
    $stmt = $pdo->prepare('SELECT 1 FROM team_members WHERE team_id=? AND user_id=? LIMIT 1');
    $stmt->execute([$team_id,$user_id]);
    if (!$stmt->fetch()) {
        $pdo->rollBack();
        echo json_encode(['success'=>false,'error'=>'not_member']); exit;
    }

    // mark completed
    $upd = $pdo->prepare('UPDATE team_tasks SET completed_by=?, completed_at=NOW() WHERE task_id=?');
    $upd->execute([$user_id,$task_id]);

    // award points to user
    $pts = (int)$task['points'];
    $upd2 = $pdo->prepare('UPDATE users SET points = points + ? WHERE user_id=?');
    $upd2->execute([$pts,$user_id]);

    // create a new random task for the team
    $pool = [
        ['title'=>'團隊步行 5000 步','points'=>10],
        ['title'=>'一起喝 8 杯水','points'=>8],
        ['title'=>'團體做 20 分鐘伸展','points'=>12],
        ['title'=>'完成 30 分鐘有氧運動','points'=>15],
        ['title'=>'共同完成 10000 步（分攤）','points'=>18],
        ['title'=>'早睡 8 小時一次','points'=>8],
        ['title'=>'完成 10 次深蹲','points'=>7],
        ['title'=>'完成 15 分鐘核心訓練','points'=>9],
        ['title'=>'團隊騎車 5 公里','points'=>14]
    ];
    $pick = $pool[array_rand($pool)];
    $ins = $pdo->prepare('INSERT INTO team_tasks (team_id,title,points) VALUES (?,?,?)');
    $ins->execute([$team_id,$pick['title'],$pick['points']]);

    // get the new task_id
    $new_task_id = (int)$pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'success'=>true,
        'awarded_points'=>$pts,
        'new_task'=>[
            'task_id'=>$new_task_id,
            'team_id'=>$team_id,
            'title'=>$pick['title'],
            'points'=>$pick['points']
        ]
    ]);
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success'=>false,'error'=>'exception','message'=>$e->getMessage()]);
    exit;
}

?>
