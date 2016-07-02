<?php


function createHour($hour) {
  return $formatHour = date("H:i", strtotime($hour));
}