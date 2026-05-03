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
function ar($x1,$y1,$x2,$y2,$mid,$lbl='',$lx=null,$ly=null){
  $hw=strlen($lbl)*3.6+10; $hh=16;
  $s="<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke=\"#333\" ".
     "stroke-width=\"1.5\" marker-end=\"url(#$mid)\"/>";
  if($lbl!==''){
    $tx=$lx??($x1+$x2)/2; $ty=$ly??($y1+$y2)/2;
    $s.="<rect x=\"".($tx-$hw/2)."\" y=\"".($ty-$hh+2)."\" width=\"$hw\" height=\"$hh\" ".
        "fill=\"white\" opacity=\"0.93\" rx=\"3\"/>".
        "<text x=\"$tx\" y=\"$ty\" text-anchor=\"middle\" ".
        "font-family=\"Arial\" font-size=\"12\" fill=\"#111\">$lbl</text>";
  }
  return $s;
}
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
   DIAGRAM 7 — LEVEL 2: Process 5 — Payment & Transactions
   Sub-processes:
     5.1 Create GCash Payment Source (PayMongo API)
     5.2 Poll / Verify Payment Status
     5.3 Record Transaction to D5
     5.4 Mark Reservation / Tournament as Paid
     5.5 Collect Remaining Balance (Staff — Walk-in)
══════════════════════════════════════════════════════════════════════ */
$d7 = "<svg width=\"960\" height=\"760\" xmlns=\"http://www.w3.org/2000/svg\">\n"
  .mk("a7")

  /* External entities */
  .ent(20,330,135,85,"Customer")
  .ent(805,120,135,85,"Staff /","Admin")

  /* Data stores — left */
  .ds(28,90,205,40,"D2","Reservations")
  .ds(28,230,205,40,"D6","Tournaments")
  /* Data stores — right */
  .ds(720,90,205,40,"D5","Transactions")

  /* Sub-processes — vertical center */
  .proc(378,75,190,88,"5.1","Create GCash Source","(PayMongo API)")
  .proc(378,240,190,88,"5.2","Poll and Verify","Payment Status")
  .proc(378,410,190,88,"5.3","Record Transaction","to D5")
  .proc(378,580,190,88,"5.4","Mark Reservation or","Tournament as Paid")
  .proc(378,690,190,70,"5.5","Collect Remaining Balance (Staff)")

  /* ── Customer → 5.1 ── */
  .ar(155,352,378,118,"a7","Payment request",260,222)

  /* ── 5.1 → Customer: redirect ── */
  .ar(378,118,155,362,"a7","GCash checkout redirect",245,228)

  /* ── 5.1 → 5.2 ── */
  .ar(473,163,473,240,"a7","Source ID created",498,203)

  /* ── 5.2 → 5.2 self: polls PayMongo ── */
  .par("M568 268 C640 268 640 302 568 302","a7","Poll PayMongo API",640,285)

  /* ── 5.2 → 5.3 on paid ── */
  .ar(473,328,473,410,"a7","Payment confirmed",498,371)

  /* ── 5.3 → D5 write ── */
  .ar(568,453,720,108,"a7","Write transaction record",660,272)

  /* ── 5.3 → 5.4 ── */
  .ar(473,498,473,580,"a7","Transaction ID",498,541)

  /* ── 5.4 → D2 mark paid ── */
  .ar(378,622,233,118,"a7","Update reservation paid=1",280,356)

  /* ── 5.4 → D6 mark paid ── */
  .ar(378,634,233,248,"a7","Update tournament paid=1",276,428)

  /* ── 5.4 → Customer: receipt ── */
  .ar(378,622,155,370,"a7","Payment receipt",240,488)

  /* ── 5.4 → 5.5 (walk-in balance) ── */
  .ar(473,668,473,690,"a7","Balance remaining",498,680)

  /* ── Staff → 5.5 collect ── */
  .ar(805,158,568,718,"a7","Collect cash balance",700,438)

  /* ── 5.5 → D5 update ── */
  .ar(568,720,720,130,"a7","Update transaction settled",660,418)

  /* ── 5.2 → Customer: failed ── */
  .ar(378,284,155,362,"a7","Payment failed / expired",240,306)

  ."</svg>";

/* ══════════════════════════════════════════════════════════════════════
   DIAGRAM 8 — LEVEL 2: Process 6 — Tournament Management
   Sub-processes:
     6.1 Create Tournament (Admin)
     6.2 Set Status: Open / Closed / Ongoing / Done
     6.3 Customer Registers and Pays Fee
     6.4 Verify Payment and Confirm Slot
     6.5 Manage Participants (Admin)
     6.6 View Tournament Reports
══════════════════════════════════════════════════════════════════════ */
$d8 = "<svg width=\"980\" height=\"860\" xmlns=\"http://www.w3.org/2000/svg\">\n"
  .mk("a8")

  /* External entities */
  .ent(20,380,135,85,"Customer")
  .ent(825,120,135,85,"Staff /","Admin")

  /* Data stores */
  .ds(28,100,205,40,"D6","Tournaments")
  .ds(28,240,215,40,"D8","Participants")
  .ds(720,100,205,40,"D5","Transactions")

  /* Sub-processes */
  .proc(375,85,195,88,"6.1","Create Tournament","(Admin)")
  .proc(375,255,195,88,"6.2","Set Tournament","Status")
  .proc(375,430,195,88,"6.3","Customer Registers","and Pays Fee")
  .proc(375,605,195,88,"6.4","Verify Payment","and Confirm Slot")
  .proc(660,430,195,88,"6.5","Manage","Participants (Admin)")
  .proc(375,755,195,75,"6.6","View Tournament Reports")

  /* ── Admin → 6.1 create ── */
  .ar(825,158,570,128,"a8","Create tournament form",706,132)

  /* ── 6.1 → D6 write ── */
  .ar(375,128,233,118,"a8","Write tournament record",296,112)

  /* ── 6.1 → 6.2 ── */
  .ar(472,173,472,255,"a8","Tournament created",498,216)

  /* ── Admin → 6.2 change status ── */
  .ar(825,175,570,298,"a8","Update status command",706,230)

  /* ── 6.2 → D6 update ── */
  .ar(375,298,233,128,"a8","Update status field",290,202)

  /* ── 6.2 → 6.3: open for registration ── */
  .ar(472,343,472,430,"a8","Status: Open",498,388)

  /* ── Customer → 6.3 register ── */
  .ar(155,402,375,472,"a8","Register + pay fee",248,430)

  /* ── 6.3 → D8 write participant ── */
  .ar(375,472,233,258,"a8","Write participant record",290,352)

  /* ── 6.3 → D5 write fee ── */
  .ar(570,472,720,118,"a8","Write fee transaction",654,282)

  /* ── 6.3 → 6.4 ── */
  .ar(472,518,472,605,"a8","Payment pending",498,563)

  /* ── 6.4 → D8 confirm slot ── */
  .ar(375,648,233,268,"a8","Update slot confirmed",290,448)

  /* ── 6.4 → D5 verify payment ── */
  .ar(570,648,720,130,"a8","Read payment status",660,382)

  /* ── 6.4 → Customer confirmation ── */
  .ar(375,648,155,420,"a8","Slot confirmed notice",238,530)

  /* ── Admin → 6.5 manage ── */
  .ar(825,193,855,472,"a8","Manage participants",864,334)

  /* ── 6.5 ↔ D8 ── */
  .ar(660,472,243,258,"a8","Read participants list",428,348)
  .ar(243,268,660,482,"a8","Update participant",442,362)

  /* ── 6.5 → 6.6 report data ── */
  .ar(757,518,660,798,"a8","Participant data",728,658)

  /* ── 6.6 → D6 + D8 read ── */
  .ar(375,792,233,268,"a8","Read all tournament data",288,528)
  .ar(660,792,720,138,"a8","Read all transactions",698,462)

  /* ── Admin → 6.6 view ── */
  .ar(825,210,570,792,"a8","View tournament reports",720,500)

  /* ── 6.6 → Admin output ── */
  .ar(570,792,825,228,"a8","Tournament report data",714,510)

  ."</svg>";

/* ══════════════════════════════════════════════════════════════════════
   DIAGRAM 9 — LEVEL 2: Process 7 — Dashboard & Stats / Overview
   Sub-processes:
     7.1 Load Overview Stats (Today's revenue, sessions, reservations)
     7.2 Display Live Sessions Panel
     7.3 Display Upcoming Reservations
     7.4 Display Cancellation Stats and Streak Info
     7.5 Generate Revenue and Session Reports
     7.6 Admin Notification Panel (new reservations, alerts)
══════════════════════════════════════════════════════════════════════ */
$d9 = "<svg width=\"980\" height=\"900\" xmlns=\"http://www.w3.org/2000/svg\">\n"
  .mk("a9")

  /* External entities */
  .ent(20,400,135,85,"Customer")
  .ent(825,400,135,85,"Staff /","Admin")

  /* Data stores — left */
  .ds(28,80,195,40,"D1","Users")
  .ds(28,190,205,40,"D2","Reservations")
  .ds(28,300,215,40,"D3","Cancellations")
  /* Data stores — right */
  .ds(730,80,195,40,"D4","Sessions")
  .ds(730,190,205,40,"D5","Transactions")
  .ds(730,300,205,40,"D6","Tournaments")

  /* Sub-processes — two columns */
  .proc(375,68,195,88,"7.1","Load Overview","Stats")
  .proc(375,235,195,88,"7.2","Display Live","Sessions Panel")
  .proc(375,405,195,88,"7.3","Display Upcoming","Reservations")
  .proc(375,575,195,88,"7.4","Display Cancellation","Stats and Streaks")
  .proc(375,745,195,88,"7.5","Generate Revenue","and Session Reports")
  .proc(660,235,195,88,"7.6","Admin Notification","Panel")

  /* ── Staff/Admin → 7.1 (load dashboard) ── */
  .ar(825,420,570,112,"a9","Open dashboard",706,254)

  /* ── Customer → 7.3 (view reservations) ── */
  .ar(155,422,375,448,"a9","View my reservations",250,428)

  /* ── Customer → 7.4 (view cancellations) ── */
  .ar(155,438,375,618,"a9","View my cancellations",238,524)

  /* ── 7.1 reads all stores ── */
  .ar(375,112,223,98,"a9","Read today sessions",280,94)
  .ar(375,122,223,208,"a9","Read today reservations",280,158)
  .ar(730,98,570,112,"a9","Read live sessions",656,96)
  .ar(730,208,570,122,"a9","Read today transactions",656,158)

  /* ── 7.1 → Staff output ── */
  .ar(570,112,825,420,"a9","KPI summary cards",706,254)

  /* ── 7.2 reads D4 ── */
  .ar(730,108,570,278,"a9","Read active sessions",660,188)
  .ar(375,278,223,208,"a9","Session details",280,236)

  /* ── 7.2 → 7.6 alert ── */
  .ar(570,278,660,278,"a9","New session started",616,268)

  /* ── 7.2 → Staff ── */
  .ar(570,278,825,432,"a9","Live sessions list",706,348)

  /* ── 7.3 reads D2 ── */
  .ar(375,448,233,208,"a9","Read upcoming reservations",286,318)
  .ar(730,208,570,448,"a9","Reservation details",656,322)

  /* ── 7.3 → Customer output ── */
  .ar(375,448,155,438,"a9","My reservations",240,436)

  /* ── 7.3 → 7.6 alert ── */
  .ar(570,448,660,298,"a9","Pending reservation alert",616,368)

  /* ── 7.4 reads D3 and D1 ── */
  .ar(375,618,233,318,"a9","Read cancellation log",280,458)
  .ar(375,628,223,108,"a9","Read user streak",280,358)

  /* ── 7.4 → Customer ── */
  .ar(375,618,155,452,"a9","Cancellation history",238,526)

  /* ── 7.5 reads D4 and D5 ── */
  .ar(375,788,223,318,"a9","Read all logs",280,550)
  .ar(730,208,570,788,"a9","Read all transactions",656,494)
  .ar(730,118,570,798,"a9","Read session durations",658,456)

  /* ── 7.5 → Staff report ── */
  .ar(570,788,825,448,"a9","Revenue and stats report",706,616)

  /* ── 7.6 reads D2 for new pending ── */
  .ar(730,208,855,278,"a9","Poll pending reservations",800,232)

  /* ── 7.6 → Staff alerts ── */
  .ar(855,323,825,438,"a9","New booking alert",842,382)

  ."</svg>";

/* ══════════════════════════════════════════════════════════════════════
   ASSEMBLE — append Batch 3 to existing file
══════════════════════════════════════════════════════════════════════ */
function sec($title,$sub,$svg){
  return "<div class=\"section\"><div class=\"dtitle\">$title</div>".
         "<div class=\"dsub\">$sub</div>$svg</div>";
}

$existing = file_get_contents($out);
$existing = str_replace("\n</body>\n</html>", '', $existing);
$existing = str_replace('Batch 1 + 2 of 3', 'Complete — All 9 Diagrams', $existing);

$append  = "<hr>\n";
$append .= sec(
  "Diagram 7 — Level 2: Process 5 — Payment &amp; Transactions",
  "Sub-processes: 5.1 Create GCash Source (PayMongo) &middot; 5.2 Poll and Verify Payment ".
  "&middot; 5.3 Record Transaction &middot; 5.4 Mark as Paid &middot; 5.5 Collect Remaining Balance (Staff)",
  $d7);
$append .= "<hr>\n";
$append .= sec(
  "Diagram 8 — Level 2: Process 6 — Tournament Management",
  "Sub-processes: 6.1 Create Tournament &middot; 6.2 Set Status &middot; 6.3 Register and Pay ".
  "&middot; 6.4 Verify Payment and Confirm Slot &middot; 6.5 Manage Participants &middot; 6.6 View Reports",
  $d8);
$append .= "<hr>\n";
$append .= sec(
  "Diagram 9 — Level 2: Process 7 — Dashboard &amp; Stats / Overview",
  "Sub-processes: 7.1 Load Overview Stats &middot; 7.2 Live Sessions Panel &middot; ".
  "7.3 Upcoming Reservations &middot; 7.4 Cancellation Stats and Streaks &middot; ".
  "7.5 Revenue Reports &middot; 7.6 Admin Notification Panel",
  $d9);
$append .= "\n</body>\n</html>";

file_put_contents($out, $existing . $append);
echo "Batch 3 appended. All 9 diagrams complete.\n";
