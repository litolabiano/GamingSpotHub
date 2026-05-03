<?php
$out = __DIR__ . '/dfd.html';

/* ── HELPERS ─────────────────────────────────────────────────────────── */
function mk($id){
  return "<defs><marker id=\"$id\" markerWidth=\"10\" markerHeight=\"7\" ".
         "refX=\"9\" refY=\"3.5\" orient=\"auto\">".
         "<polygon points=\"0 0,10 3.5,0 7\" fill=\"#333\"/></marker></defs>";
}
function ent($x,$y,$w,$h,...$lines){
  $cx=$x+$w/2; $cy=$y+$h/2-(count($lines)-1)*11;
  $t='';
  foreach($lines as $i=>$l)
    $t.="<text x=\"$cx\" y=\"".($cy+$i*22)."\" text-anchor=\"middle\" ".
        "font-family=\"Arial\" font-size=\"16\" font-weight=\"bold\" fill=\"#222\">$l</text>";
  return "<rect x=\"$x\" y=\"$y\" width=\"$w\" height=\"$h\" rx=\"4\" ".
         "fill=\"#F9DC5C\" stroke=\"#555\" stroke-width=\"2.5\"/>$t";
}
function proc($x,$y,$w,$h,$num,...$lines){
  $cx=$x+$w/2; $base=$y+$h/2-(count($lines))*10;
  $t="<text x=\"$cx\" y=\"$base\" text-anchor=\"middle\" ".
     "font-family=\"Arial\" font-size=\"13\" font-weight=\"bold\" fill=\"#222\">$num</text>";
  foreach($lines as $i=>$l)
    $t.="<text x=\"$cx\" y=\"".($base+16+$i*17)."\" text-anchor=\"middle\" ".
        "font-family=\"Arial\" font-size=\"13\" fill=\"#222\">$l</text>";
  return "<rect x=\"$x\" y=\"$y\" width=\"$w\" height=\"$h\" rx=\"16\" ".
         "fill=\"#F9E97E\" stroke=\"#555\" stroke-width=\"2\"/>$t";
}
function ds($x,$y,$w,$h,$did,$name){
  $lx=$x+38; $mid=$y+$h/2+5;
  return "<rect x=\"$x\" y=\"$y\" width=\"$w\" height=\"$h\" ".
         "fill=\"#fff\" stroke=\"#555\" stroke-width=\"1.5\"/>".
         "<line x1=\"$lx\" y1=\"$y\" x2=\"$lx\" y2=\"".($y+$h)."\" ".
         "stroke=\"#555\" stroke-width=\"1.5\"/>".
         "<text x=\"".($x+19)."\" y=\"$mid\" text-anchor=\"middle\" ".
         "font-family=\"Arial\" font-size=\"13\" font-weight=\"bold\" fill=\"#222\">$did</text>".
         "<text x=\"".($lx+($w-38)/2)."\" y=\"$mid\" text-anchor=\"middle\" ".
         "font-family=\"Arial\" font-size=\"13\" fill=\"#222\">$name</text>";
}
/* labelled straight arrow with white pill background */
function ar($x1,$y1,$x2,$y2,$mid,$lbl='',$lx=null,$ly=null){
  $hw=strlen($lbl)*3.6+10; $hh=16;
  $s="<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke=\"#333\" ".
     "stroke-width=\"1.5\" marker-end=\"url(#$mid)\"/>";
  if($lbl!==''){
    $tx=$lx??($x1+$x2)/2; $ty=$ly??($y1+$y2)/2;
    $s.="<rect x=\"".($tx-$hw/2)."\" y=\"".($ty-$hh+2)."\" width=\"$hw\" height=\"$hh\" ".
        "fill=\"white\" opacity=\"0.93\" rx=\"3\"/>".
        "<text x=\"$tx\" y=\"".($ty)."\" text-anchor=\"middle\" ".
        "font-family=\"Arial\" font-size=\"12\" fill=\"#111\">$lbl</text>";
  }
  return $s;
}
/* curved path arrow */
function par($d,$mid,$lbl='',$lx=null,$ly=null){
  $hw=strlen($lbl)*3.6+10; $hh=16;
  $s="<path d=\"$d\" stroke=\"#333\" stroke-width=\"1.5\" fill=\"none\" ".
     "marker-end=\"url(#$mid)\"/>";
  if($lbl!==''&&$lx!==null){
    $s.="<rect x=\"".($lx-$hw/2)."\" y=\"".($ly-$hh+2)."\" width=\"$hw\" height=\"$hh\" ".
        "fill=\"white\" opacity=\"0.93\" rx=\"3\"/>".
        "<text x=\"$lx\" y=\"$ly\" text-anchor=\"middle\" ".
        "font-family=\"Arial\" font-size=\"12\" fill=\"#111\">$lbl</text>";
  }
  return $s;
}

/* ══════════════════════════════════════════════════════════════════════
   DIAGRAM 1 — CONTEXT DIAGRAM (Level 0)
══════════════════════════════════════════════════════════════════════ */
$d1 = "<svg width=\"980\" height=\"440\" xmlns=\"http://www.w3.org/2000/svg\">\n"
  .mk("ah")
  .ent(20,180,145,90,"Customer")
  .proc(400,162,200,136,"0","GSpot Gaming","Hub System")
  .ent(815,180,145,90,"Staff /","Admin")
  /* Customer → System */
  .ar(165,196,400,195,"ah","Register / Login",282,182)
  .ar(165,210,400,210,"ah","Book Reservation",282,197)
  .ar(165,224,400,223,"ah","Cancel Reservation",282,211)
  .ar(165,238,400,237,"ah","View Dashboard",282,225)
  .ar(165,252,400,251,"ah","Register for Tournament",282,239)
  /* System → Customer */
  .par("M400 170 C315 95 188 95 165 183","ah","Confirmation / Notifications",282,82)
  .ar(400,278,165,274,"ah","Stats and History",282,292)
  .ar(400,292,165,288,"ah","Reschedule Notice",282,306)
  /* Staff → System */
  .ar(815,196,600,195,"ah","Start / End Session",706,182)
  .ar(815,210,600,210,"ah","Manage Reservations",706,197)
  .ar(815,224,600,223,"ah","Admin Cancel / Reschedule",706,211)
  .ar(815,238,600,237,"ah","Manage Tournaments",706,225)
  /* System → Staff */
  .par("M600 170 C682 95 800 95 815 183","ah","Reports and Analytics",706,82)
  .ar(600,278,815,274,"ah","Live Notifications",706,292)
  ."</svg>";

/* ══════════════════════════════════════════════════════════════════════
   DIAGRAM 2 — LEVEL 1 DFD
   Layout matches the reference picture style closely
══════════════════════════════════════════════════════════════════════ */
$d2 = "<svg width=\"1080\" height=\"1100\" xmlns=\"http://www.w3.org/2000/svg\">\n"
  .mk("a1")
  /* External entities */
  .ent(20,520,135,85,"Customer")
  .ent(925,520,135,85,"Staff/","Admin")
  /* ── Data stores ── */
  .ds(200,158,195,40,"D1","Users")
  .ds(200,300,205,40,"D2","Reservations")
  .ds(200,480,195,40,"D3","Cancellations")
  .ds(760,380,185,40,"D4","Sessions")
  .ds(760,660,200,40,"D5","Transactions")
  .ds(760,860,200,40,"D6","Tournaments")
  .ds(200,860,205,40,"D7","Reschedules")
  /* ── Processes (vertical column) ── */
  .proc(548,95,190,90,"1.","Authentication")
  .proc(548,270,190,90,"2.","Reservation","Management")
  .proc(548,460,190,90,"3.","Session","Management")
  .proc(330,590,190,90,"4.","Cancellation","Handling")
  .proc(548,680,190,90,"5.","Payment &","Transactions")
  .proc(330,840,190,90,"6.","Tournament","Management")
  .proc(548,940,190,90,"7.","Dashboard","& Stats")

  /* ── Customer flows ── */
  .ar(155,535,548,140,"a1","Login / Register",332,306)
  .ar(155,548,548,310,"a1","Book Reservation",332,406)
  .ar(155,562,330,635,"a1","Cancel Request",228,605)
  .ar(155,576,330,882,"a1","Register Tournament",218,732)
  .ar(155,590,548,984,"a1","View Dashboard",318,790)

  /* ── Process 1 (Auth) ↔ D1 Users ── */
  .ar(548,140,395,178,"a1","Write user data",466,150)
  .ar(395,168,548,150,"a1","Read user data",466,162)

  /* ── Process 1 → 2 ── */
  .ar(643,185,643,270,"a1","Authenticated user",668,230)

  /* ── Process 2 ↔ D2 Reservations ── */
  .ar(548,310,405,310,"a1","Reservation data",472,298)
  .ar(405,320,548,320,"a1","Read reservation",472,335)

  /* ── Staff → Process 2 confirm ── */
  .ar(925,535,738,310,"a1","Confirm / Convert",836,400)

  /* ── Process 2 → D7 (reschedule) ── */
  .ar(548,320,405,872,"a1","Reschedule log",450,588)

  /* ── Process 2 → Process 3 ── */
  .ar(643,360,643,460,"a1","Session start",668,413)

  /* ── Staff → Process 3 ── */
  .ar(925,550,738,505,"a1","Start / End Session",832,510)

  /* ── Process 3 ↔ D4 Sessions ── */
  .ar(738,500,945,398,"a1","Session data",848,432)
  .ar(945,388,738,490,"a1","Read session",848,418)

  /* ── D4 → Process 4 ── */
  .ar(760,400,520,620,"a1","Session data",635,498)

  /* ── Staff → Process 4 (admin cancel) ── */
  .ar(925,564,520,634,"a1","Admin Cancel",722,588)

  /* ── Process 4 → D3 ── */
  .ar(330,634,395,490,"a1","Cancellation log",340,558)

  /* ── Process 4 → D1 (streak) ── */
  .ar(330,610,395,178,"a1","Update streak",348,385)

  /* ── Process 3 → Process 5 (payment) ── */
  .ar(643,550,643,680,"a1","Session cost",668,618)

  /* ── Process 5 ↔ D5 Transactions ── */
  .ar(738,720,960,672,"a1","Transaction data",856,682)
  .ar(960,662,738,710,"a1","Read transactions",856,670)

  /* ── Process 6 ↔ D6 Tournaments ── */
  .ar(520,882,760,872,"a1","Tournament records",642,860)
  .ar(760,882,520,892,"a1","Read tournament",638,896)

  /* ── Staff → Process 6 ── */
  .ar(925,580,520,862,"a1","Manage Tournaments",724,718)

  /* ── Process 7 ↔ D7 ── */
  .ar(548,984,405,880,"a1","Read all stores",468,930)

  /* ── Staff → Process 7 ── */
  .ar(925,598,738,984,"a1","View Reports",834,792)

  /* ── Output back to Customer ── */
  .ar(548,270,155,548,"a1","Reservation confirmed",318,388)
  .ar(330,680,155,562,"a1","Cancel result",226,618)
  .ar(548,984,155,590,"a1","Dashboard view",316,790)

  ."</svg>";

/* ══════════════════════════════════════════════════════════════════════
   DIAGRAM 3 — LEVEL 2: Authentication & User Management
   Clean layout — clear label positions, no overlaps
══════════════════════════════════════════════════════════════════════ */
$d3 = "<svg width=\"920\" height=\"720\" xmlns=\"http://www.w3.org/2000/svg\">\n"
  .mk("a3")
  /* entities */
  .ent(20,300,135,85,"Customer")
  .ent(755,300,135,85,"Staff /","Admin")
  /* D1 at top-right, clearly separated from processes */
  .ds(590,38,200,40,"D1","Users")

  /* Sub-processes in a vertical stack, left-center */
  .proc(348,35,185,88,"1.1","Validate","Registration")
  .proc(348,205,185,88,"1.2","Send Email","Verification")
  .proc(348,375,185,88,"1.3","Authenticate","Login")
  .proc(348,545,185,88,"1.4","Password Reset")

  /* ── Customer → 1.1 Register ── */
  .ar(155,322,348,78,"a3","Register data",242,192)

  /* ── 1.1 → D1 write (above the store) ── */
  .par("M533 60 C555 28 575 28 590 50","a3","Write new user",558,20)

  /* ── D1 → 1.1 check duplicate (curved back) ── */
  .par("M590 60 C568 85 548 80 533 75","a3","Check duplicate",564,90)

  /* ── 1.1 → 1.2 ── */
  .ar(440,123,440,205,"a3","Account created",468,166)

  /* ── 1.2 → Customer (verification link) ── */
  .ar(348,248,155,340,"a3","Verification email sent",238,285)

  /* ── 1.2 → D1 mark verified ── */
  .ar(533,248,590,70,"a3","Mark email verified",575,148)

  /* ── Customer → 1.3 Login ── */
  .ar(155,348,348,418,"a3","Email + password",238,376)

  /* ── 1.3 → D1 read hash ── */
  .ar(533,418,590,62,"a3","Read password hash",570,225)

  /* ── 1.3 → Staff/Admin (auth session) ── */
  .ar(533,418,755,342,"a3","Auth session (staff)",644,372)

  /* ── 1.3 → Customer (auth session) ── */
  .ar(348,418,155,358,"a3","Auth session (customer)",236,380)

  /* ── Customer → 1.4 Password Reset ── */
  .ar(155,370,348,588,"a3","Reset request",238,482)

  /* ── 1.4 → D1 update password ── */
  .ar(533,588,590,78,"a3","Update password hash",568,328)

  /* ── 1.4 → Customer (reset sent) ── */
  .ar(348,588,155,382,"a3","Reset link sent",236,485)

  ."</svg>";

/* ══════════════════════════════════════════════════════════════════════
   ASSEMBLE HTML
══════════════════════════════════════════════════════════════════════ */
function sec($title,$sub,$svg){
  return "<div class=\"section\"><div class=\"dtitle\">$title</div>".
         "<div class=\"dsub\">$sub</div>$svg</div>";
}

$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GSpot Gaming Hub — DFD (Batch 1 of 3)</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Arial,Helvetica,sans-serif;background:#fff;padding:36px}
.cover{text-align:center;margin-bottom:50px;padding-bottom:24px;border-bottom:2px solid #ccc}
.cover h1{font-size:28px;font-weight:bold;color:#222}
.cover p{font-size:14px;color:#666;margin-top:6px}
.section{margin-bottom:68px;text-align:center}
.dtitle{font-size:19px;font-weight:bold;color:#222;margin-bottom:4px}
.dsub{font-size:13px;color:#666;margin-bottom:18px}
svg{display:block;margin:0 auto;overflow:visible}
hr{border:none;border-top:2px solid #ddd;margin:48px 0}
</style>
</head>
<body>
<div class="cover">
  <h1>GSpot Gaming Hub</h1>
  <p>Data Flow Diagrams &mdash; Batch 1 of 3</p>
</div>
HTML;

$html .= sec("Diagram 1 — Context Diagram (Level 0)",
  "System boundary: all external entities and top-level data flows",$d1);
$html .= "<hr>";
$html .= sec("Diagram 2 — Level 1 DFD: All System Processes",
  "Processes 1–7 with all data stores and primary data flows",$d2);
$html .= "<hr>";
$html .= sec("Diagram 3 — Level 2: Process 1 — Authentication &amp; User Management",
  "Sub-processes: 1.1 Validate Registration &middot; 1.2 Send Email Verification ".
  "&middot; 1.3 Authenticate Login &middot; 1.4 Password Reset",$d3);

$html .= "\n</body>\n</html>";
file_put_contents($out, $html);
echo "Written: $out\n";
