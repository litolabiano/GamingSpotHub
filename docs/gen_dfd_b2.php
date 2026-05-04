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
   DIAGRAM 4 — LEVEL 2: Process 2 — Reservation Management
   Sub-processes:
     2.1 Check Ban Status
     2.2 Check Console Availability
     2.3 Validate & Submit Reservation
     2.4 Process GCash Payment (PayMongo)
     2.5 Confirm / Convert Reservation (Staff)
══════════════════════════════════════════════════════════════════════ */
$d4 = "<svg width=\"960\" height=\"760\" xmlns=\"http://www.w3.org/2000/svg\">\n"
  .mk("a4")

  /* External entities */
  .ent(20,350,135,85,"Customer")
  .ent(800,350,135,85,"Staff /","Admin")

  /* Data stores — left column */
  .ds(28,90,195,40,"D1","Users")
  .ds(28,220,205,40,"D2","Reservations")
  .ds(28,540,205,40,"D7","Reschedules")

  /* Data stores — right column */
  .ds(720,90,205,40,"D5","Transactions")
  .ds(720,220,195,40,"D4","Sessions")

  /* Sub-processes — vertical center */
  .proc(378,75,190,88,"2.1","Check Ban","Status")
  .proc(378,235,190,88,"2.2","Check Console","Availability")
  .proc(378,400,190,88,"2.3","Validate and","Submit Reservation")
  .proc(378,565,190,88,"2.4","Process GCash","Payment (PayMongo)")
  .proc(378,690,190,70,"2.5","Confirm / Convert (Staff)")

  /* ── Customer → 2.1 book request ── */
  .ar(155,372,378,118,"a4","Book request",262,232)

  /* ── 2.1 ↔ D1 check ban ── */
  .ar(378,118,223,108,"a4","Read ban status",296,98)
  .ar(223,118,378,128,"a4","Ban status",296,132)

  /* ── 2.1 → 2.2 ── */
  .ar(473,163,473,235,"a4","No active ban",498,200)

  /* ── 2.2 ↔ D4 check sessions ── */
  .ar(568,278,720,238,"a4","Read active sessions",644,250)
  .ar(720,248,568,268,"a4","Availability result",644,262)

  /* ── 2.2 → 2.3 ── */
  .ar(473,323,473,400,"a4","Units available",498,363)

  /* ── Customer → 2.3 form data ── */
  .ar(155,385,378,442,"a4","Reservation form data",252,408)

  /* ── 2.3 → D2 write reservation ── */
  .ar(378,442,233,242,"a4","Write reservation",285,332)

  /* ── 2.3 → 2.4 payment ── */
  .ar(473,488,473,565,"a4","Payment required",498,528)

  /* ── 2.4 → D5 write transaction ── */
  .ar(568,608,720,108,"a4","Write transaction",660,348)

  /* ── 2.4 → D2 update paid ── */
  .ar(378,608,233,252,"a4","Update paid status",292,418)

  /* ── 2.4 → Customer GCash redirect ── */
  .ar(378,608,155,398,"a4","GCash checkout redirect",248,500)

  /* ── Staff → 2.5 confirm ── */
  .ar(800,388,568,720,"a4","Confirm / Convert",690,550)

  /* ── 2.5 ↔ D2 update status ── */
  .ar(378,720,233,260,"a4","Update status",295,488)

  /* ── Staff → 2.5 reschedule ── */
  .ar(800,405,568,730,"a4","Reschedule",686,566)

  /* ── 2.5 → D7 write reschedule ── */
  .ar(378,730,233,558,"a4","Write reschedule log",288,642)

  /* ── 2.3 → Customer confirmation ── */
  .ar(378,400,155,375,"a4","Reservation confirmed",248,378)

  ."</svg>";

/* ══════════════════════════════════════════════════════════════════════
   DIAGRAM 5 — LEVEL 2: Process 3 — Session Management
   Sub-processes:
     3.1 Start Session (Walk-in or Reservation)
     3.2 Track Live Session & Timer
     3.3 Extend Session (Staff-Direct or Customer Request)
     3.4 End Session & Calculate Bill
     3.5 Correct End-Time (Admin)
══════════════════════════════════════════════════════════════════════ */
$d5 = "<svg width=\"960\" height=\"760\" xmlns=\"http://www.w3.org/2000/svg\">\n"
  .mk("a5")

  /* External entities */
  .ent(20,350,135,85,"Customer")
  .ent(800,180,135,85,"Staff /","Admin")

  /* Data stores */
  .ds(28,80,195,40,"D4","Sessions")
  .ds(28,210,205,40,"D2","Reservations")
  .ds(720,80,205,40,"D5","Transactions")

  /* Sub-processes */
  .proc(378,68,190,88,"3.1","Start Session","Walk-in or Reserved")
  .proc(378,228,190,88,"3.2","Track Live","Session and Timer")
  .proc(378,400,190,88,"3.3","Extend Session","Direct or Request")
  .proc(378,568,190,88,"3.4","End Session","and Calculate Bill")
  .proc(378,690,190,70,"3.5","Correct End-Time (Admin)")

  /* ── Staff → 3.1 start ── */
  .ar(800,218,568,112,"a5","Start session command",692,152)

  /* ── 3.1 ↔ D2 read reservation ── */
  .ar(378,112,233,228,"a5","Read reservation",290,162)
  .ar(233,218,378,122,"a5","Reservation data",292,152)

  /* ── 3.1 → D4 write session ── */
  .ar(378,112,223,98,"a5","Write session",296,96)

  /* ── 3.1 → 3.2 ── */
  .ar(473,156,473,228,"a5","Session active",498,194)

  /* ── 3.2 ↔ D4 update ── */
  .ar(378,270,223,100,"a5","Update session data",285,178)
  .ar(223,90,378,252,"a5","Read session",296,162)

  /* ── 3.2 → 3.3 (customer extension request) ── */
  .ar(473,316,473,400,"a5","Running session",498,360)

  /* ── Customer → 3.3 extend request ── */
  .ar(155,368,378,442,"a5","Extension request",252,402)

  /* ── Staff → 3.3 direct extend ── */
  .ar(800,235,568,444,"a5","Direct extension",692,338)

  /* ── 3.3 → D4 update planned minutes ── */
  .ar(378,444,223,110,"a5","Update planned minutes",280,268)

  /* ── 3.3 → D5 extension charge ── */
  .ar(568,444,720,108,"a5","Extension charge",654,268)

  /* ── 3.3 → Customer result ── */
  .ar(378,444,155,380,"a5","Extension granted / denied",235,408)

  /* ── 3.3 → 3.4 ── */
  .ar(473,488,473,568,"a5","Session continues",498,530)

  /* ── Staff → 3.4 end ── */
  .ar(800,252,568,610,"a5","End session command",700,430)

  /* ── 3.4 → D4 write final ── */
  .ar(378,610,223,120,"a5","Write end time and cost",282,358)

  /* ── 3.4 → D5 write transaction ── */
  .ar(568,610,720,120,"a5","Write final transaction",654,358)

  /* ── 3.4 → Customer receipt ── */
  .ar(378,610,155,392,"a5","Session cost receipt",238,498)

  /* ── 3.4 → 3.5 ── */
  .ar(473,656,473,690,"a5","Completed session",498,674)

  /* ── Staff → 3.5 correction ── */
  .ar(800,268,568,720,"a5","Corrected end time",696,492)

  /* ── 3.5 → D4 recalculate ── */
  .ar(378,720,223,130,"a5","Recalculate and update",278,424)

  /* ── 3.5 → D5 adjust transaction ── */
  .ar(568,720,720,130,"a5","Adjust transaction",654,424)

  ."</svg>";

/* ══════════════════════════════════════════════════════════════════════
   DIAGRAM 6 — LEVEL 2: Process 4 — Cancellation Handling
   Sub-processes:
     4.1 Receive and Validate Cancel Request
     4.2 Apply Grace Period Rule (30 min)
     4.3 Record Cancellation (User)
     4.4 Update Streak and Apply Ban
     4.5 Admin-Initiated Cancellation
     4.6 My Cancellations — View History
══════════════════════════════════════════════════════════════════════ */
$d6 = "<svg width=\"980\" height=\"840\" xmlns=\"http://www.w3.org/2000/svg\">\n"
  .mk("a6")

  /* External entities */
  .ent(20,360,135,85,"Customer")
  .ent(820,130,135,85,"Staff /","Admin")

  /* Data stores */
  .ds(28,110,205,40,"D2","Reservations")
  .ds(28,250,215,40,"D3","Cancellations Log")
  .ds(28,540,195,40,"D1","Users")
  .ds(680,540,215,40,"D3","Cancellations Log")

  /* Sub-processes */
  .proc(370,95,195,88,"4.1","Receive and Validate","Cancel Request")
  .proc(370,265,195,88,"4.2","Apply 30-Min","Grace Period Rule")
  .proc(370,435,195,88,"4.3","Record Cancellation","and Update D2")
  .proc(370,605,195,88,"4.4","Update Streak","and Apply Ban")
  .proc(660,265,195,88,"4.5","Admin-Initiated","Cancellation")
  .proc(370,745,195,75,"4.6","My Cancellations — View History")

  /* ── Customer → 4.1 ── */
  .ar(155,382,370,138,"a6","Reservation ID + reason",252,246)

  /* ── 4.1 ↔ D2 validate reservation ── */
  .ar(370,138,233,128,"a6","Read reservation",296,118)
  .ar(233,118,370,148,"a6","Reservation data",296,132)

  /* ── 4.1 → 4.2 ── */
  .ar(467,183,467,265,"a6","Valid cancel request",492,226)

  /* ── 4.2 → 4.3 (outside grace) ── */
  .ar(467,353,467,435,"a6","Outside 30-min grace",492,396)

  /* ── 4.2 → Customer (inside grace) ── */
  .ar(370,308,155,388,"a6","Within grace — no penalty",244,340)

  /* ── 4.3 → D2 update status ── */
  .ar(370,478,233,140,"a6","Update status = cancelled",280,296)

  /* ── 4.3 → D3 Cancellations Log ── */
  .ar(370,478,233,268,"a6","Write cancellation log",286,360)

  /* ── 4.3 → 4.4 ── */
  .ar(467,523,467,605,"a6","Non-grace cancellation",492,566)

  /* ── 4.4 → D1 update streak ── */
  .ar(370,648,223,558,"a6","Increment streak",285,596)

  /* ── 4.4 → D1 apply ban ── */
  .ar(370,658,223,568,"a6","Set banned_until (if 3rd)",275,620)

  /* ── 4.4 → Customer warning / ban notice ── */
  .ar(370,648,155,402,"a6","Warning or ban notice",248,522)

  /* ── 4.4 → 4.6 ── */
  .ar(467,693,467,745,"a6","Log entry ready",492,720)

  /* ── Staff → 4.5 ── */
  .ar(820,168,855,308,"a6","Admin cancel + reason",862,238)

  /* ── 4.5 → D2 update status ── */
  .ar(660,308,233,148,"a6","Update status = cancelled",410,220)

  /* ── 4.5 → D3 right write log ── */
  .ar(660,318,895,558,"a6","Write log (cancelled_by=admin)",782,430)

  /* ── 4.5 note: no streak impact ── */
  .ar(660,328,370,468,"a6","No streak impact",508,388)

  /* ── 4.6 ↔ D3 right read history ── */
  .ar(565,782,895,568,"a6","Read cancellation history",736,672)
  .ar(895,558,565,772,"a6","Cancellation records",722,656)

  /* ── 4.6 → Customer ── */
  .ar(370,782,155,412,"a6","My Cancellations list",245,594)

  ."</svg>";

/* ══════════════════════════════════════════════════════════════════════
   ASSEMBLE HTML — append to existing Batch 1 file
══════════════════════════════════════════════════════════════════════ */
function sec($title,$sub,$svg){
  return "<div class=\"section\"><div class=\"dtitle\">$title</div>".
         "<div class=\"dsub\">$sub</div>$svg</div>";
}

/* Read existing Batch 1 content */
$existing = file_get_contents($out);

/* Strip closing </body></html> so we can append */
$existing = str_replace("\n</body>\n</html>", '', $existing);

/* Update cover subtitle */
$existing = str_replace(
  'Batch 1 of 3',
  'Batch 1 + 2 of 3',
  $existing
);

$append  = "<hr>\n";
$append .= sec(
  "Diagram 4 — Level 2: Process 2 — Reservation Management",
  "Sub-processes: 2.1 Check Ban Status &middot; 2.2 Check Console Availability ".
  "&middot; 2.3 Validate and Submit Reservation &middot; 2.4 Process GCash Payment ".
  "&middot; 2.5 Confirm / Convert Reservation (Staff)",
  $d4);
$append .= "<hr>\n";
$append .= sec(
  "Diagram 5 — Level 2: Process 3 — Session Management",
  "Sub-processes: 3.1 Start Session &middot; 3.2 Track Live Session &amp; Timer ".
  "&middot; 3.3 Extend Session &middot; 3.4 End Session and Calculate Bill ".
  "&middot; 3.5 Correct End-Time",
  $d5);
$append .= "<hr>\n";
$append .= sec(
  "Diagram 6 — Level 2: Process 4 — Cancellation Handling",
  "Sub-processes: 4.1 Validate Cancel Request &middot; 4.2 Apply 30-Min Grace Period ".
  "&middot; 4.3 Record Cancellation &middot; 4.4 Update Streak &amp; Apply Ban ".
  "&middot; 4.5 Admin-Initiated Cancellation &middot; 4.6 My Cancellations History",
  $d6);
$append .= "\n</body>\n</html>";

file_put_contents($out, $existing . $append);
echo "Batch 2 appended to: $out\n";
