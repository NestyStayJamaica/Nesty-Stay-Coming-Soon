<?php
header("Content-Type: application/json");

$data=json_decode(file_get_contents("tracker.json"),true);

echo json_encode([
  "platinum_total"=>$data["platinum_total"],
  "platinum_filled"=>$data["platinum_filled"],
  "platinum_left"=>$data["platinum_total"]-$data["platinum_filled"],
  "gold_total"=>$data["gold_total"],
  "gold_filled"=>$data["gold_filled"],
  "gold_left"=>$data["gold_total"]-$data["gold_filled"]
]);
