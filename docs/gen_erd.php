<?php
$out = __DIR__ . '/erd.html';

/* ── COLOUR THEMES ─────────────────────────────────────────────────── */
$C = [
  'blue'   => ['hd'=>'#4A8FC4','bd'=>'#D6EDF8'],
  'green'  => ['hd'=>'#4E9A6A','bd'=>'#CDE9D4'],
  'purple' => ['hd'=>'#7B64B2','bd'=>'#E3D8F4'],
  'tan'    => ['hd'=>'#9E8A44','bd'=>'#EEE8C0'],
  'rose'   => ['hd'=>'#B05070','bd'=>'#F4D8E0'],
];

/* ── ENTITY BOX ─────────────────────────────────────────────────────── */
function ebox($x,$y,$w,$name,$th,&$attrs){
  $rh=23; $hh=30; $kw=34;
  $h=$hh+count($attrs)*$rh;
  $cx=$x+$w/2;
  $s ="<rect x='$x' y='$y' width='$w' height='$h' rx='4' fill='{$th['bd']}' stroke='#888' stroke-width='1.5'/>";
  $s.="<rect x='$x' y='$y' width='$w' height='$hh' rx='4' fill='{$th['hd']}'/>";
  $s.="<rect x='$x' y='".($y+$hh-4)."' width='$w' height='4' fill='{$th['hd']}'/>";
  $s.="<text x='$cx' y='".($y+20)."' text-anchor='middle' font-family='Segoe UI,Arial' font-size='13' font-weight='bold' fill='white'>$name</text>";
  $s.="<line x1='".($x+$kw)."' y1='".($y+$hh)."' x2='".($x+$kw)."' y2='".($y+$h)."' stroke='#aaa' stroke-width='1'/>";
  foreach($attrs as $i=>$a){
    $ry=$y+$hh+$i*$rh;
    if($i%2===1) $s.="<rect x='$x' y='$ry' width='$w' height='$rh' fill='rgba(0,0,0,0.04)'/>";
    $s.="<line x1='$x' y1='".($ry+$rh)."' x2='".($x+$w)."' y2='".($ry+$rh)."' stroke='#ddd' stroke-width='0.5'/>";
    if(!empty($a[0])){
      $kc=$a[0]==='PK'?'#D49020':'#5080C0';
      $s.="<rect x='".($x+3)."' y='".($ry+4)."' width='28' height='15' rx='3' fill='$kc'/>";
      $s.="<text x='".($x+17)."' y='".($ry+15)."' text-anchor='middle' font-family='Segoe UI,Arial' font-size='10' font-weight='bold' fill='white'>{$a[0]}</text>";
    }
    $s.="<text x='".($x+$kw+6)."' y='".($ry+16)."' font-family='Segoe UI,Arial' font-size='12' fill='#111'>{$a[1]}</text>";
  }
  return ['svg'=>$s,'x'=>$x,'y'=>$y,'w'=>$w,'h'=>$h];
}

/* ── CROW-FOOT LINE ─────────────────────────────────────────────────── */
// side: 'l','r','t','b'  pt: offset along that side (0=near top/left, 1=near bottom/right)
function side_pt($e,$side,$pt=0.5){
  switch($side){
    case 'l': return [$e['x'],               $e['y']+$e['h']*$pt];
    case 'r': return [$e['x']+$e['w'],       $e['y']+$e['h']*$pt];
    case 't': return [$e['x']+$e['w']*$pt,  $e['y']];
    case 'b': return [$e['x']+$e['w']*$pt,  $e['y']+$e['h']];
  }
}
function cf_line($x1,$y1,$x2,$y2,$bx,$by,$one,$many){
  // Draw path: (x1,y1) → bend(bx,by) → (x2,y2)
  $s="<path d='M $x1 $y1 L $bx $by L $x2 $y2' stroke='#555' stroke-width='1.5' fill='none'/>";
  // "one" tick at (x1,y1) — direction from bend
  $s.=one_tick($x1,$y1,$bx,$by);
  // "many" crow at (x2,y2) — direction from bend
  if($many) $s.=crow($x2,$y2,$bx,$by); else $s.=one_tick($x2,$y2,$bx,$by);
  return $s;
}
function straight($x1,$y1,$x2,$y2,$many2=true,$many1=false){
  $s="<line x1='$x1' y1='$y1' x2='$x2' y2='$y2' stroke='#555' stroke-width='1.5'/>";
  if($many1) $s.=crow($x1,$y1,$x2,$y2); else $s.=one_tick($x1,$y1,$x2,$y2);
  if($many2) $s.=crow($x2,$y2,$x1,$y1); else $s.=one_tick($x2,$y2,$x1,$y1);
  return $s;
}
function one_tick($ex,$ey,$fx,$fy){
  // Perpendicular tick at endpoint (ex,ey), away from (fx,fy)
  $len=7;
  $dx=$ex-$fx; $dy=$ey-$fy; $d=max(sqrt($dx*$dx+$dy*$dy),0.01);
  $nx=-$dy/$d; $ny=$dx/$d;
  $ax=$ex+$nx*$len; $ay=$ey+$ny*$len;
  $bx=$ex-$nx*$len; $by=$ey-$ny*$len;
  return "<line x1='$ax' y1='$ay' x2='$bx' y2='$by' stroke='#555' stroke-width='1.5'/>";
}
function crow($ex,$ey,$fx,$fy){
  // Crow-foot at endpoint (ex,ey) pointing toward (fx,fy)
  $d2=8; $spread=7;
  $dx=$fx-$ex; $dy=$fy-$ey; $d=max(sqrt($dx*$dx+$dy*$dy),0.01);
  $ux=$dx/$d; $uy=$dy/$d;
  $nx=-$uy; $ny=$ux;
  // Back point along the line
  $bx=$ex+$ux*$d2; $by=$ey+$uy*$d2;
  $s ="<line x1='$ex' y1='$ey' x2='".($bx+$nx*$spread)."' y2='".($by+$ny*$spread)."' stroke='#555' stroke-width='1.5'/>";
  $s.="<line x1='$ex' y1='$ey' x2='".($bx-$nx*$spread)."' y2='".($by-$ny*$spread)."' stroke='#555' stroke-width='1.5'/>";
  // Also draw the tick
  $tx=$ex+$nx*7; $ty=$ey+$ny*7;
  $ux2=$ex-$nx*7; $uy2=$ey-$ny*7;
  $s.="<line x1='$tx' y1='$ty' x2='$ux2' y2='$uy2' stroke='#555' stroke-width='1.5'/>";
  return $s;
}

/* ══════════════════════════════════════════════════════════════════════
   DEFINE ALL ENTITIES
══════════════════════════════════════════════════════════════════════ */
$W=240; // entity width

// ── users ──────────────────────────────────────────────────────────
$a_users=[
  ['PK','user_id'],['','email'],['','password_hash'],['','full_name'],
  ['','phone'],['','role (customer/shopkeeper/owner)'],['','status'],
  ['','consecutive_cancellations'],['','reservation_banned_until'],
  ['','email_verified'],['','created_at'],
];
$e_users=ebox(320,20,$W,'users',$C['blue'],$a_users);

// ── consoles ───────────────────────────────────────────────────────
$a_con=[
  ['PK','console_id'],['','console_name'],['','console_type (PS5/Xbox)'],
  ['','unit_number'],['','status'],['','hourly_rate'],['','created_at'],
];
$e_con=ebox(640,20,$W,'consoles',$C['green'],$a_con);

// ── games ──────────────────────────────────────────────────────────
$a_games=[
  ['PK','game_id'],['','game_name'],['','console_type'],['','genre'],
  ['','is_available'],['','is_new_release'],['','added_date'],
];
$e_games=ebox(950,20,$W,'games',$C['purple'],$a_games);

// ── notifications ──────────────────────────────────────────────────
$a_notif=[
  ['PK','notification_id'],['FK','user_id'],
  ['','type'],['','message'],['','is_read'],['','created_at'],
];
$e_notif=ebox(20,20,$W,'notifications',$C['rose'],$a_notif);

// ── reservations ───────────────────────────────────────────────────
$a_res=[
  ['PK','reservation_id'],['FK','user_id'],['FK','console_id'],
  ['FK','created_by'],['','console_type'],['','rental_mode'],
  ['','planned_minutes'],['','reserved_date'],['','reserved_time'],
  ['','downpayment_amount'],['','downpayment_method'],
  ['','status'],['','cancel_reason_type'],['','cancelled_by'],
  ['','created_at'],['','updated_at'],
];
$e_res=ebox(320,400,$W,'reservations',$C['blue'],$a_res);

// ── gaming_sessions ────────────────────────────────────────────────
$a_sess=[
  ['PK','session_id'],['FK','user_id'],['FK','console_id'],
  ['FK','created_by'],['','rental_mode'],['','start_time'],
  ['','end_time'],['','duration_minutes'],['','extended_minutes'],
  ['','total_cost'],['','status'],
];
$e_sess=ebox(640,240,$W,'gaming_sessions',$C['blue'],$a_sess);

// ── reservation_cancellations ──────────────────────────────────────
$a_can=[
  ['PK','cancel_id'],['FK','reservation_id'],['FK','user_id'],
  ['','cancelled_by (user/admin)'],['','cancel_reason_type'],
  ['','cancel_reason_detail'],['','is_within_grace (30min)'],
  ['','reserved_date'],['','downpayment_amount'],
  ['','refund_issued'],['','cancelled_at'],
];
$e_can=ebox(20,390,$W,'reservation_cancellations',$C['tan'],$a_can);

// ── reschedules ────────────────────────────────────────────────────
$a_rsch=[
  ['PK','reschedule_id'],['FK','reservation_id'],['FK','rescheduled_by'],
  ['','original_date'],['','original_time'],
  ['','new_date'],['','new_time'],
  ['','reason_type (typhoon/outage/emergency/other)'],
  ['','reason_detail'],['','created_at'],
];
$e_rsch=ebox(20,200,$W,'reschedules',$C['tan'],$a_rsch);

// ── session_extensions ─────────────────────────────────────────────
$a_ext=[
  ['PK','extension_id'],['FK','session_id'],['FK','requested_by'],
  ['FK','approved_by'],['','extra_minutes'],['','extra_cost'],
  ['','payment_method'],['','status'],['','requested_at'],['','resolved_at'],
];
$e_ext=ebox(640,620,$W,'session_extensions',$C['tan'],$a_ext);

// ── transactions ───────────────────────────────────────────────────
$a_tx=[
  ['PK','transaction_id'],['FK','session_id'],['FK','user_id'],
  ['FK','processed_by'],['','amount'],['','payment_method (gcash)'],
  ['','payment_status'],['','no_refund_acknowledged'],['','transaction_date'],
];
$e_tx=ebox(640,890,$W,'transactions',$C['tan'],$a_tx);

// ── tournaments ────────────────────────────────────────────────────
$a_tour=[
  ['PK','tournament_id'],['FK','game_id'],['','tournament_name'],
  ['','console_type'],['','start_date'],['','end_date'],
  ['','entry_fee'],['','prize_pool'],['','max_participants'],
  ['','status'],['','announcement'],['','created_at'],
];
$e_tour=ebox(950,230,$W,'tournaments',$C['green'],$a_tour);

// ── tournament_participants ────────────────────────────────────────
$a_tp=[
  ['PK','participant_id'],['FK','tournament_id'],['FK','user_id'],
  ['FK','registered_by'],['','ign'],['','contact_number'],
  ['','gcash_proof'],['','payment_status'],['','placement'],
  ['','prize_amount'],['','registration_date'],
];
$e_tp=ebox(950,620,$W,'tournament_participants',$C['blue'],$a_tp);

/* ── Collect entity SVGs ── */
$entities=[$e_users,$e_con,$e_games,$e_notif,$e_res,$e_sess,
           $e_can,$e_rsch,$e_ext,$e_tx,$e_tour,$e_tp];

/* ══════════════════════════════════════════════════════════════════════
   DRAW RELATIONSHIPS  (one side always uses one_tick, many uses crow)
══════════════════════════════════════════════════════════════════════ */
$rels='';

// users ──< notifications  (right of notif → left of users)
[$x1,$y1]=side_pt($e_notif,'r',0.5);
[$x2,$y2]=side_pt($e_users,'l',0.5);
$rels.=straight($x1,$y1,$x2,$y2,false,true); // many notifs per user

// users ──< reservations  (vertical: bottom of users → top of reservations)
[$x1,$y1]=side_pt($e_users,'b',0.3);
[$x2,$y2]=side_pt($e_res,'t',0.3);
$rels.=straight($x1,$y1,$x2,$y2,true,false);

// users ──< gaming_sessions  (right of users → left of gaming_sessions)
[$x1,$y1]=side_pt($e_users,'r',0.45);
[$x2,$y2]=side_pt($e_sess,'l',0.2);
$rels.=straight($x1,$y1,$x2,$y2,true,false);

// consoles ──< reservations  (left of consoles → right of reservations)
[$x1,$y1]=side_pt($e_con,'l',0.6);
[$x2,$y2]=side_pt($e_res,'r',0.2);
$rels.=cf_line($x1,$y1,$x2,$y2,
  ($e_con['x']+$e_res['x']+$e_res['w'])/2,$y1,
  false,true);

// consoles ──< gaming_sessions  (vertical: bottom of consoles → top of sessions)
[$x1,$y1]=side_pt($e_con,'b',0.5);
[$x2,$y2]=side_pt($e_sess,'t',0.5);
$rels.=straight($x1,$y1,$x2,$y2,true,false);

// reservations ──< reservation_cancellations  (left of res → right of cancel)
[$x1,$y1]=side_pt($e_res,'l',0.25);
[$x2,$y2]=side_pt($e_can,'r',0.25);
$rels.=straight($x1,$y1,$x2,$y2,true,false);

// reservations ──< reschedules  (left of res → right of rsch)
[$x1,$y1]=side_pt($e_res,'l',0.15);
[$x2,$y2]=side_pt($e_rsch,'r',0.75);
$rels.=straight($x1,$y1,$x2,$y2,true,false);

// users ──< reservation_cancellations  (bottom of notif col → top of cancel)
[$x1,$y1]=side_pt($e_notif,'b',0.5);
[$x2,$y2]=side_pt($e_can,'t',0.5);
$rels.=straight($x1,$y1,$x2,$y2,true,false);

// users ──< reschedules  (left of users → right of rsch, bent)
[$x1,$y1]=side_pt($e_users,'l',0.2);
[$x2,$y2]=side_pt($e_rsch,'r',0.25);
$mid_x=($x1+$x2)/2;
$rels.=cf_line($x1,$y1,$x2,$y2,$mid_x,$y1,false,true);

// gaming_sessions ──< session_extensions  (vertical)
[$x1,$y1]=side_pt($e_sess,'b',0.5);
[$x2,$y2]=side_pt($e_ext,'t',0.5);
$rels.=straight($x1,$y1,$x2,$y2,true,false);

// gaming_sessions ──< transactions  (vertical through extensions)
[$x1,$y1]=side_pt($e_sess,'b',0.3);
[$x2,$y2]=side_pt($e_tx,'t',0.3);
// bent line around extensions
$rels.=cf_line($x1,$y1,$x2,$y2,$x1,$y2,false,true);

// users ──< transactions  (right of users bent down to left of tx)
[$x1,$y1]=side_pt($e_users,'r',0.6);
[$x2,$y2]=side_pt($e_tx,'l',0.4);
$rels.=cf_line($x1,$y1,$x2,$y2,$x1,$y2,false,true);

// games ──< tournaments  (vertical)
[$x1,$y1]=side_pt($e_games,'b',0.5);
[$x2,$y2]=side_pt($e_tour,'t',0.5);
$rels.=straight($x1,$y1,$x2,$y2,true,false);

// tournaments ──< tournament_participants  (vertical)
[$x1,$y1]=side_pt($e_tour,'b',0.5);
[$x2,$y2]=side_pt($e_tp,'t',0.5);
$rels.=straight($x1,$y1,$x2,$y2,true,false);

// users ──< tournament_participants  (right of users → left of tp, bent)
[$x1,$y1]=side_pt($e_users,'r',0.75);
[$x2,$y2]=side_pt($e_tp,'l',0.5);
$rels.=cf_line($x1,$y1,$x2,$y2,$x1+40,$y1,false,true);

/* ── Compute canvas size ── */
$maxX=0; $maxY=0;
foreach($entities as $e){ $maxX=max($maxX,$e['x']+$e['w']); $maxY=max($maxY,$e['y']+$e['h']); }
$cW=$maxX+40; $cH=$maxY+40;

/* ── Assemble SVG ── */
$svg="<svg width='$cW' height='$cH' xmlns='http://www.w3.org/2000/svg'>\n";
$svg.="<rect width='$cW' height='$cH' fill='#F8F8F8'/>\n";
$svg.=$rels."\n";
foreach($entities as $e) $svg.=$e['svg']."\n";
$svg.="</svg>";

/* ── Legend ── */
$legend='<div style="display:flex;gap:24px;flex-wrap:wrap;justify-content:center;margin:18px 0 8px">
<span style="display:flex;align-items:center;gap:6px"><span style="display:inline-block;width:14px;height:14px;background:#D49020;border-radius:3px"></span><b style="font-size:12px">PK</b> Primary Key</span>
<span style="display:flex;align-items:center;gap:6px"><span style="display:inline-block;width:14px;height:14px;background:#5080C0;border-radius:3px"></span><b style="font-size:12px">FK</b> Foreign Key</span>
<span style="display:flex;align-items:center;gap:6px"><span style="display:inline-block;width:28px;height:3px;background:#555"></span><b style="font-size:12px">│─&lt;</b> One-to-Many (crow\'s foot)</span>
</div>';

/* ── Write HTML ── */
$html=<<<H
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GSpot Gaming Hub — ERD</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Segoe UI,Arial,sans-serif;background:#f0f0f0;padding:32px}
.cover{text-align:center;margin-bottom:28px}
.cover h1{font-size:26px;font-weight:700;color:#222}
.cover p{font-size:13px;color:#666;margin-top:4px}
.wrap{background:#fff;border-radius:8px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.1);overflow-x:auto}
</style>
</head>
<body>
<div class="cover">
  <h1>GSpot Gaming Hub — Entity Relationship Diagram (ERD)</h1>
  <p>Complete ERD covering all 12 entities and their relationships</p>
</div>
$legend
<div class="wrap">$svg</div>
</body>
</html>
H;

file_put_contents($out,$html);
echo "Written: $out\n";
echo "Canvas: {$cW}px × {$cH}px\n";
echo "Entities: ".count($entities)."\n";
