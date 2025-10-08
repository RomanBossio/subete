<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function api($ruta) {
  $url = "http://localhost/subete/backend/api/panel/$ruta";
  $json = @file_get_contents($url);
  if ($json === false) return ['error' => "No se pudo obtener $ruta"];
  $data = json_decode($json, true);
  return is_array($data) ? $data : ['error' => "Respuesta inválida"];
}

$usuarios    = api("usuarios_totales.php");
$viajes      = api("viajes_publicados.php");
$nuevos      = api("nuevos_usuarios_semana.php");
$confirmados = api("viajes_confirmados.php");

$usuariosTotal     = (int)($usuarios['total'] ?? 0);
$viajesTotal       = (int)($viajes['total'] ?? 0);
$nuevosSemanaTotal = (int)($nuevos['total'] ?? 0);
$confirmadosTotal  = (int)($confirmados['total'] ?? 0);

$tasaConfirmados = $viajesTotal > 0 ? round(($confirmadosTotal / $viajesTotal) * 100, 1) : 0.0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Panel de Control - Súbete</title>
  <link rel="stylesheet" href="/subete/frontend/css/app.css?v=1.0">

  <style>
  :root{
    --color-primary:#2196f3;
    --color-secondary:#1976d2;
    --color-bg:#f4f6f9;
    --color-card:#ffffff;
    --color-text:#1f2937;
    --shadow:0 4px 12px rgba(0,0,0,.08);
    --shadow-lg:0 10px 24px rgba(0,0,0,.12);
    --radius:16px;
  }
  body{
    background-color:var(--color-bg);
    font-family:'Segoe UI',sans-serif;
    color:var(--color-text);
    margin:0;
    padding:0;
  }
  .container{
    max-width:1200px;
    margin:auto;
    padding:20px;
  }
  .dashboard-title{
    font-weight:700;
    font-size:2.2rem;
    margin:10px 0 30px;
    color:var(--color-primary);
    text-align:center;
  }
  .kpi-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
    gap:20px;
  }
  .card{
    background:var(--color-card);
    border:none;
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    padding:20px;
    transition:transform .15s ease,box-shadow .15s ease;
  }
  .card:hover{
    transform:translateY(-3px);
    box-shadow:var(--shadow-lg);
  }
  .card h5{
    font-size:1.1rem;
    margin-bottom:.6rem;
    color:#374151;
    display:flex;
    align-items:center;
    gap:6px;
  }
  .kpi{
    font-size:2rem;
    line-height:1.2;
    font-weight:600;
    text-align:center;
    color:var(--color-secondary);
  }
  .badge{
    display:inline-block;
    padding:.3rem .5rem;
    border-radius:999px;
    font-weight:600;
    font-size:.8rem;
    background:#e8f2ff;
    color:var(--color-secondary);
    margin-left:auto;
  }
  .charts-section{
    margin-top:40px;
  }
  .charts-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
    gap:25px;
    align-items:start;
  }
  .card canvas{
    width:100%!important;
    height:auto!important;
    max-height:260px!important;
  }
  @media(max-width:768px){
    .dashboard-title{font-size:1.7rem;}
    .kpi{font-size:1.6rem;}
    .card canvas{max-height:220px!important;}
  }
  </style>
</head>

<body>
  <?php require __DIR__ . '/partials/header.php'; ?>

  <div class="container">
    <h1 class="dashboard-title">Panel de Control - Súbete </h1>

    <!-- ===== KPIs ===== -->
    <div class="kpi-grid">
      <div class="card">
        <h5>👥 Usuarios registrados <span class="badge">Total</span></h5>
        <div class="kpi"><?= $usuariosTotal ?></div>
      </div>
      <div class="card">
        <h5>🚘 Viajes publicados <span class="badge">Disponibles</span></h5>
        <div class="kpi"><?= $viajesTotal ?></div>
      </div>
      <div class="card">
        <h5>🧍 Nuevos esta semana <span class="badge">7 días</span></h5>
        <div class="kpi"><?= $nuevosSemanaTotal ?></div>
      </div>
      <div class="card">
        <h5>✅ Viajes confirmados <span class="badge">Completados</span></h5>
        <div class="kpi"><?= $confirmadosTotal ?></div>
      </div>
      <div class="card">
        <h5>📈 Tasa de confirmación <span class="badge">Eficiencia</span></h5>
        <div class="kpi"><?= $tasaConfirmados ?>%</div>
      </div>
    </div>

    <!-- ===== GRÁFICOS ===== -->
    <section class="charts-section">
      <h2 class="dashboard-title" style="font-size:1.6rem;"> Estadísticas generales</h2>

      <div class="charts-grid">
        <div class="card">
          <canvas id="graficoViajes"></canvas>
        </div>
        <div class="card">
          <canvas id="graficoUsuarios"></canvas>
        </div>
        <div class="card" style="grid-column:1/-1;">
          <canvas id="graficoSemana"></canvas>
        </div>
      </div>
    </section>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    async function obtenerDatos(ruta){
      const r = await fetch(`/subete/backend/api/panel/${ruta}`);
      return r.json();
    }

    (async () => {
      const viajesPublicados = await obtenerDatos("viajes_publicados.php");
      const viajesConfirmados = await obtenerDatos("viajes_confirmados.php");
      const usuariosTotales = await obtenerDatos("usuarios_totales.php");
      const nuevosUsuarios = await obtenerDatos("nuevos_usuarios_semana.php");
      const viajesSemana = await obtenerDatos("viajes_semana.php");

      // === Gráfico 1: Viajes (dona) ===
      new Chart(document.getElementById('graficoViajes'), {
        type: 'doughnut',
        data: {
          labels: ['Publicados', 'Confirmados'],
          datasets: [{
            data: [Number(viajesPublicados.total||0), Number(viajesConfirmados.total||0)],
            backgroundColor: ['#4CAF50','#2196F3']
          }]
        },
        options: {
          plugins:{ title:{ display:true, text:'Distribución de viajes' } }
        }
      });

      // === Gráfico 2: Usuarios (barras) ===
      new Chart(document.getElementById('graficoUsuarios'), {
        type: 'bar',
        data: {
          labels: ['Totales','Nuevos 7d'],
          datasets: [{
            label: 'Usuarios',
            data: [Number(usuariosTotales.total||0), Number(nuevosUsuarios.total||0)],
            backgroundColor: ['#9C27B0','#FF9800']
          }]
        },
        options: {
          scales:{ y:{ beginAtZero:true } },
          plugins:{ title:{ display:true, text:'Usuarios registrados' } }
        }
      });

      // === Gráfico 3: Actividad semanal (barras) ===
      const dias = {
        Monday:'Lunes',Tuesday:'Martes',Wednesday:'Miércoles',
        Thursday:'Jueves',Friday:'Viernes',Saturday:'Sábado',Sunday:'Domingo'
      };
      const labelsSemana = viajesSemana.map(v => dias[v.dia_nombre] || v.dia_nombre);
      const datosSemana = viajesSemana.map(v => v.total);

      new Chart(document.getElementById('graficoSemana'), {
        type: 'bar',
        data: {
          labels: labelsSemana,
          datasets: [{
            label: 'Viajes publicados por día',
            data: datosSemana,
            backgroundColor: '#2196F3'
          }]
        },
        options: {
          plugins:{ title:{ display:true, text:'Actividad semanal de viajes' } },
          scales:{
            y:{
              beginAtZero:true,
              ticks:{
                callback:function(value){
                  if(value>=1000) return (value/1000)+'K';
                  return value;
                }
              }
            }
          }
        }
      });
    })();
  </script>
</body>
</html>
