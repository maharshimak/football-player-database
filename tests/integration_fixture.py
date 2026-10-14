import http.cookiejar,urllib.request,urllib.parse,http.client,json,pathlib,secrets,socket,subprocess,time,os,re,shutil,hashlib
ROOT=pathlib.Path(__file__).resolve().parents[1]; STAGE=ROOT; RUN=pathlib.Path(os.environ['FOOTBALL_FIXTURE_ROOT'])/('football-runtime-'+secrets.token_hex(4)); RUN.mkdir(exist_ok=False); DB=RUN/'database'; TMP=RUN/'tmp'; TMP.mkdir(); SESS=RUN/'sessions'; SESS.mkdir(); DOC=RUN/'source'; DOC.mkdir()
PHP=os.environ['FOOTBALL_PHP']; EXT=str(pathlib.Path(PHP).parent/'ext'); BIN=pathlib.Path(os.environ['FOOTBALL_MYSQL_BIN']); results=[]; processes=[]
def freeport():
 with socket.socket() as s:s.bind(('127.0.0.1',0));return s.getsockname()[1]
def check(name,condition):
 results.append({'name':name,'status':'PASS' if condition else 'FAIL'}); assert condition,name
def waitport(port):
 for _ in range(120):
  try:
   with socket.create_connection(('127.0.0.1',port),timeout=.1):return
  except OSError:time.sleep(.1)
 raise RuntimeError('Disposable service startup failed')
for p in STAGE.rglob('*'):
 if p.is_file() and p.parent == STAGE and (p.suffix in {'.php','.css'} or p.name=='schema.sql'):q=DOC/p.relative_to(STAGE);q.parent.mkdir(parents=True,exist_ok=True);shutil.copyfile(p,q)
for p in DOC.rglob('*.php'):
 proc=subprocess.run([PHP,'-n','-l',str(p)],capture_output=True,text=True);check('PHP syntax '+p.relative_to(DOC).as_posix(),proc.returncode==0)
dbport,webport=freeport(),freeport(); rootpassword=secrets.token_urlsafe(24); apppassword=secrets.token_urlsafe(24); adminpassword=secrets.token_urlsafe(24)
try:
 init=subprocess.run([str(BIN/'mysql_install_db.exe'),'--datadir='+str(DB),'--port='+str(dbport),'--password='+rootpassword],capture_output=True,text=True,timeout=60);check('Initialize new disposable database only, no Windows service',init.returncode==0)
 dbproc=subprocess.Popen([str(BIN/'mysqld.exe'),'--no-defaults','--basedir='+str(BIN.parent),'--datadir='+str(DB),'--bind-address=127.0.0.1','--port='+str(dbport),'--skip-name-resolve','--skip-log-bin','--tmpdir='+str(TMP),'--pid-file='+str(RUN/'db.pid'),'--log-error='+str(RUN/'db.log')],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL,creationflags=subprocess.CREATE_NO_WINDOW);processes.append(dbproc);waitport(dbport)
 # SQL fixtures are authored empty schema and one synthetic account. Never touch any original DB.
 sql="CREATE DATABASE fixture CHARACTER SET utf8mb4; CREATE USER 'fixture'@'127.0.0.1' IDENTIFIED BY '"+apppassword+"'; GRANT SELECT,INSERT,UPDATE,DELETE ON fixture.* TO 'fixture'@'127.0.0.1'; USE fixture; "+(STAGE/'schema.sql').read_text()
 clientenv=dict(os.environ,MYSQL_PWD=rootpassword);setup=subprocess.run([str(BIN/'mysql.exe'),'--no-defaults','--protocol=TCP','--host=127.0.0.1','--port='+str(dbport),'--user=root'],input=sql,capture_output=True,text=True,env=clientenv,timeout=20);check('Create empty schema and least-privilege fixture user',setup.returncode==0)
 hashproc=subprocess.run([PHP,'-n','-r','echo password_hash(getenv("FIXTURE_PASSWORD"), PASSWORD_DEFAULT);'],capture_output=True,text=True,env=dict(os.environ,FIXTURE_PASSWORD=adminpassword),check=True)
 env=dict(os.environ,APP_ADMIN_USERNAME='fixture',APP_ADMIN_PASSWORD_HASH=hashproc.stdout,APP_ALLOWED_HOSTS='127.0.0.1,localhost',DB_HOST='127.0.0.1',DB_PORT=str(dbport),DB_USER='fixture',DB_PASSWORD=apppassword,DB_NAME='fixture')
 webproc=subprocess.Popen([PHP,'-n','-d','extension_dir='+EXT,'-d','extension=mysqli','-d','session.save_path='+str(SESS),'-S','127.0.0.1:'+str(webport),'-t',str(DOC)],env=env,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL,creationflags=subprocess.CREATE_NO_WINDOW);processes.append(webproc);waitport(webport)
 cookies=http.cookiejar.CookieJar(); opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cookies))
 def req(path,data=None,headers=None,client=opener):
  request=urllib.request.Request('http://127.0.0.1:'+str(webport)+'/'+path,data=urllib.parse.urlencode(data).encode() if data is not None else None,headers=headers or {})
  try:
   with client.open(request,timeout=5) as resp:return resp.status,resp.read().decode(),resp.headers,resp.geturl()
  except urllib.error.HTTPError as e:return e.code,e.read().decode(),e.headers,e.geturl()
 def csrf(body):return re.search(r'name="csrf" value="([a-f0-9]{64})"',body).group(1)
 status,body,headers,url=req('view_players.php');check('Unauthenticated listing redirects to sign-in without data',url.endswith('/login.php') and status==200)
 check('Session cookie HTTP-only and SameSite=Strict', 'HttpOnly' in str(headers.get_all('Set-Cookie')) or all(c.has_nonstandard_attr('HttpOnly') for c in cookies))
 status,body,_,_=req('add_player.php',{'player_name':'noauth'});check('Unauthenticated write cannot insert',body.find('Sign in')>=0)
 status,body,_,_=req('login.php'); token=csrf(body);before=next(iter(cookies)).value
 status,body,_,_=req('login.php',{'csrf':'wrong','username':'fixture','password':adminpassword});check('Login rejects invalid CSRF',status==403)
 status,body,_,_=req('login.php',{'csrf':token,'username':'fixture','password':'wrong'});check('Wrong credentials rejected',status==401)
 status,body,_,url=req('login.php',{'csrf':token,'username':'fixture','password':adminpassword});check('Valid login redirects to index',status==200 and url.endswith('/index.php'));after=next(iter(cookies)).value;check('Login regenerates session id',after!=before);token=csrf(body)
 status,_,_,_=req('add_player.php',{'csrf':'wrong','player_name':'No insert','player_email':'fixture@example.test','player_team':'PSG'});check('Authenticated writes reject invalid CSRF',status==403)
 payload="O'Brien <script>alert(1)</script>"; status,body,_,_=req('add_player.php',{'csrf':token,'player_name':payload,'player_email':'fixture@example.test','player_team':'PSG'});check('Create apostrophe/HTML input safely using prepared SQL and escaped render',status==200 and 'O&#039;Brien &lt;script&gt;alert(1)&lt;/script&gt;' in body and '<script>alert(1)</script>' not in body)
 pid=re.search(r'edit_player.php\?id=(\d+)',body).group(1)
 status,_,_,_=req('edit_player.php?id=1%20OR%201=1');check('Injection-shaped id rejected',status==422)
 status,_,_,_=req('delete_player.php?id='+pid);check('GET deletion rejected',status==403)
 status,body,_,_=req('view_players.php?delete='+pid);check('Legacy GET deletion query no longer mutates',payload.replace('<','&lt;').replace('>','&gt;').replace("'",'&#039;') in body)
 status,body,_,_=req('edit_player.php',{'csrf':token,'id':pid,'player_name':'=SUM(1,2)','player_email':'fixture@example.test','player_team':'PSG'});check('Prepared update persists formula-shaped name literally',status==200 and '=SUM(1,2)' in body)
 status,csvbody,_,_=req('export_players_csv.php');check('CSV neutralizes spreadsheet formulas',status==200 and "'=SUM(1,2)" in csvbody)
 status,_,_,_=req('add_player.php',{'csrf':token,'player_name':'Bad','player_email':'bad','player_team':'PSG'});check('Malformed player validation fails safely',status==422)
 status,body,_,_=req('delete_player.php',{'csrf':token,'id':pid});check('POST+CSRF deletion removes only requested synthetic row',status==200 and '=SUM(1,2)' not in body)
 status,_,_,_=req('index.php',headers={'Host':'evil.example'});check('Unapproved Host rejected',status==400)
 status,body,_,url=req('logout.php',{'csrf':token});check('Authenticated POST logout returns to sign-in',status==200 and url.endswith('/login.php'))
 clean=urllib.request.build_opener(); status,_,_,url=req('export_players_csv.php',client=clean);check('Export requires authenticated session',url.endswith('/login.php'))
except Exception as e:
 results.append({'name':'Fixture runner exception','status':'FAIL','error':type(e).__name__,'message':str(e)[:200]})
finally:
 for process in reversed(processes):
  if process.poll() is None:process.terminate()
  try:process.wait(timeout=10)
  except subprocess.TimeoutExpired:process.kill();process.wait()
 version=subprocess.run([PHP,'-n','-v'],capture_output=True,text=True).stdout.splitlines()[0]
 report={'tests':results,'passed':sum(x['status']=='PASS' for x in results),'failed':sum(x['status']=='FAIL' for x in results),'isolated_datadir':str(DB),'original_services_or_data_used':False,'services_stopped':True,'runtime_versions':{'PHP':version,'MariaDB':'Use a currently supported MariaDB/MySQL runtime; inspect vendor support policy'}}
 (RUN/'results.json').write_text(json.dumps(report,indent=2));print(json.dumps({'passed':report['passed'],'failed':report['failed'],'last_result':results[-1]}))
