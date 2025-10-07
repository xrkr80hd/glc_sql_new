<?php
function send_site_mail(string $to, string $subject, string $body, string $from = 'no-reply@golibertychurch.com'): bool {
  $headers = "From: $from\r\nReply-To: $from\r\nContent-Type: text/plain; charset=utf-8";
  return @mail($to, $subject, $body, $headers);
}
