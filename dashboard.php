<?php
session_start();
$admin = false;
$admin_pw = 't4jn3h3sl0';

if (isset($_GET['admin']) && $_GET['admin'] == $admin_pw)
  $admin = true;

$refresh = $admin ? 10 : 5;

if (!$admin && (!isset($_SESSION['WEBID_LOGGED_IN']) || !$_SESSION['WEBID_LOGGED_IN'])) {
  echo 'Please login first <a href="/user_login.php">here</a>.';
  exit;
}

?>
<html>
<head>
<link rel="stylesheet" href="dashboard/main.css">
<link rel="stylesheet" href="dashboard/jquery-ui-1.12.1.custom/jquery-ui.css">
<script src="dashboard/jquery-3.3.1.js"></script>
<script src="dashboard/jquery-ui-1.12.1.custom/jquery-ui.js"></script>
<script src="dashboard/jquery-sse.js"></script>
<meta http-equiv="refresh" content="<?=$refresh;?>">
<script>
async function place_bid(auction, bid)
{
  console.log("Auction: "+auction+" Bid: "+bid);
  $.post("bid.php", "csrftoken=<?=$_SESSION['csrftoken'];?>&bid="+bid+"&id="+auction+"&action=bid&Input=Confirm+bid");
  await new Promise(r => setTimeout(r, 500));
  location.reload();
}
</script>
</head>
<body>

<?php

require_once('/var/www/default/shared/db.class.php');

$db = new db('localhost', 'webid', 'W3B1DDDDDDe123z', 'webid');

$uid = $_SESSION['WEBID_LOGGED_IN'];
$nick = $db->query('SELECT nick FROM webid_users WHERE id='.$uid)->fetchAll()[0]['nick'];

echo "<h2>Auctions of user: ".$nick."</h2>";

if ($admin) {
  $auctions = $db->query('select a.id, a.title, a.pict_url, a.ends, a.current_bid, a.current_bid_id,
  a.closed, a.num_bids,
  (ends < UTC_TIMESTAMP()) as closed2, 
  (SELECT b.bidder FROM webid_bids b WHERE b.auction = a.id ORDER BY bidwhen DESC LIMIT 1) as last_bidder,
  -1 as my_last_bid
from webid_auctions a where
  a.id in (select auction from webid_bids)
  and starts <= UTC_TIMESTAMP() ORDER BY (closed OR closed2) ASC, title ASC')->fetchAll();
  $max_num_bids = $db->query('SELECT MAX(num_bids) as max FROM webid_auctions WHERE starts <= UTC_TIMESTAMP() AND ends >= UTC_TIMESTAMP() AND closed = 0')->fetchAll();
  echo "XX".$max_num_bids[0]['max'];
} else {
  $auctions = $db->query('select a.id, a.title, a.pict_url, a.ends, a.current_bid, a.current_bid_id,
  a.closed,
  (ends < UTC_TIMESTAMP()) as closed2, 
  (SELECT b.bidder FROM webid_bids b WHERE b.auction = a.id ORDER BY id DESC LIMIT 1) as last_bidder,
  (SELECT b2.bid FROM webid_bids b2 WHERE b2.auction = a.id AND b2.bidder = '.$uid.' ORDER BY bidwhen DESC LIMIT 1) as my_last_bid
from webid_auctions a where
  a.id in (select auction from webid_bids where bidder='.$uid.')
  and starts <= UTC_TIMESTAMP() ORDER BY (closed OR closed2) ASC, title ASC')->fetchAll();
}

echo '<div class="grid-container">';
foreach ($auctions as $a) {
  $html = '<center>'.$a['title'].'<br>';
  $html .= '<img width="100px" height="100px" src="uploaded/'.$a['id'].'/'.$a['pict_url'].'"></center>';

  $end = new DateTimeImmutable($a['ends'], new DateTimeZone("UTC"));
  $now = new DateTimeImmutable("now", new DateTimeZone("UTC"));
  $diff = $now->diff($end);

  if ($a['closed'] == '1' || $a['closed2'] == 1) {
    if ($end->add(new DateInterval("P7D")) < $now) { // closed at least 7 days ago, skip
      continue;
    }
    $bcolor = 'gray';
    goto out;
  } elseif ($a['last_bidder'] == $uid)
    $bcolor = '#58c724';
  else
    $bcolor = '#ed9e2f';

  if ($bcolor != 'gray' && $admin) {
    $max = $max_num_bids[0]['max']+1;
    $range = 400;
    $x = $range*$a['num_bids'] / 1.0 / $max;
    if ($x <= 200) {
      $r = $x;
      $g = 200;
    } else {
      $r = 200;
      $g = 200 - ($x-200);
    }
    $b = 80;
    $bcolor = '#'.str_pad(dechex($r), 2, '0', STR_PAD_LEFT).str_pad(dechex($g), 2, '0', STR_PAD_LEFT).str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
  }


  $tl = $diff->format('%dd %H:%I:%S');
  $e = $end->format('Y-m-d H:i:s T');
  $n = $now->format('Y-m-d H:i:s T');

  $html .= "<br>";
  $html .= "<br>";
  if ($end->sub(new DateInterval("PT2M")) < $now)
    $html .= "Time left: <span style='color: red'>".$tl."</span><br>";
  else
    $html .= "Time left: ".$tl."<br>";
  if ($admin)
    $html .= "No of bids: ".$a['num_bids']."<br>";
  else
    $html .= "Your last bid: ".$a['my_last_bid']."<br>";
  $html .= "Current bid: ".$a['current_bid']."<br>";

  if (!$admin) {
    foreach (array(1, 3, 5, 10, 20) as $bid)
      $html .= '<button type="button" onclick="place_bid('.$a['id'].', \''.str_replace('.', '%2C', $a['current_bid']+$bid).'\')">+'.$bid.'</button>';
  }


out:
  echo '<div class="grid-item" style="background: '.$bcolor.'">'.$html.'</div>';
}
echo '</div>';



?>
</body>
</html>
