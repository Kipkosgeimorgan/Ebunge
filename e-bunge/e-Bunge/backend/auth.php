<?php require_once __DIR__.'/../config.php';
if($_SERVER['REQUEST_METHOD']!=='POST') redirect('../login.php'); verify_csrf(); $action=$_POST['action']??'';
if($action==='login'){
  $s=db()->prepare('SELECT * FROM users WHERE work_email=? OR username=? LIMIT 1'); $s->execute([trim($_POST['identity']),trim($_POST['identity'])]); $u=$s->fetch();
  if(!$u || !password_verify($_POST['password'],$u['password_hash'])){flash('error','The sign-in details were not recognised.'); redirect('../login.php');}
  $_SESSION['user']=['id'=>$u['id'],'username'=>$u['username'],'work_email'=>$u['work_email'],'role'=>$u['role']]; audit('Signed in'); redirect('../dashboard.php');
}
if($action==='register'){
  $role=$_POST['role']??''; $password=$_POST['password']??'';
  
  // Removed prefix check from this validation line:
  if(!isset(ROLES[$role]) || !filter_var($_POST['work_email'],FILTER_VALIDATE_EMAIL) || $_POST['password']!==$_POST['confirm_password']) { 
      flash('error','Check the form. Passwords must match.'); 
      redirect('../register.php'); 
  }
  
  if($role==='speaker' && count_query('SELECT COUNT(*) FROM users WHERE role=?',['speaker'])>=2){ flash('error','The Office of the Speaker already has the maximum of two accounts.'); redirect('../register.php'); }
  try { $s=db()->prepare('INSERT INTO users(work_email,username,password_hash,role) VALUES(?,?,?,?)'); $s->execute([trim($_POST['work_email']),trim($_POST['username']),password_hash($password,PASSWORD_DEFAULT),$role]); flash('success','Account created. Please sign in.'); redirect('../login.php'); } catch(PDOException $e){ flash('error','That work email or username is already registered.'); redirect('../register.php'); }
}
if($action==='forgot'){flash('success','If the account exists, the recovery request has been recorded for administrator follow-up.'); redirect('../login.php');}
redirect('../login.php');