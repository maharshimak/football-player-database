<?php
require dirname(__DIR__) . '/lib.php'; $checks = 0;
function check(bool $condition): void { global $checks; if (!$condition) throw new RuntimeException('Fixture assertion failed'); $checks++; }
check(esc('<img src=x onerror=alert(1)>') === '&lt;img src=x onerror=alert(1)&gt;'); check(esc('"') === '&quot;');
foreach (['=1+1','+cmd','-2','@SUM(1)',"\tformula",'  =3'] as $value) check(csv_safe($value)[0] === "'"); check(csv_safe('Ada') === 'Ada');
check(csrf_valid(str_repeat('a',64),str_repeat('a',64))); check(!csrf_valid(str_repeat('b',64),str_repeat('a',64))); check(!csrf_valid('', '')); check(!csrf_valid(null, str_repeat('a',64)));
$fields = valid_player(['player_name'=>"O'Brien",'player_email'=>'fixture@example.test','player_team'=>'PSG']); check($fields[0] === "O'Brien"); check(valid_id('12') === 12);
foreach ([['player_name'=>'','player_email'=>'fixture@example.test','player_team'=>'PSG'],['player_name'=>'Ada','player_email'=>'bad','player_team'=>'PSG'],['player_name'=>'Ada','player_email'=>'fixture@example.test','player_team'=>'unknown']] as $bad) { try { valid_player($bad); check(false); } catch (InvalidArgumentException $e) {check(true);} }
foreach (['1 OR 1=1','0','-1'] as $bad) {try { valid_id($bad); check(false); } catch (InvalidArgumentException $e) {check(true);} }
$hash=password_hash('synthetic-fixture-only-password',PASSWORD_DEFAULT); check(authenticate('fixture','synthetic-fixture-only-password',['fixture',$hash])); check(!authenticate('other','synthetic-fixture-only-password',['fixture',$hash])); check(!authenticate('fixture','wrong',['fixture',$hash])); echo "PASS: $checks helper assertions\n";
