<?php include 'header.php'; ?>
<!-- Bootstrap Icon -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<style>
  .cursor-pointer { cursor: pointer; }
  /* beri background putih default juga */
  canvas { background-color:#fff; }
</style>

<div class="container-fluid px-4 mt-3">
  <div class="row align-items-center mb-4">
    <div class="col-md-8 col-12 mb-2 mb-md-0">
      <h2 class="fw-bold mb-1">Dashboard Gudang</h2>
      <div class="text-muted">Selamat datang, <b><?= htmlspecialchars($_SESSION['username']); ?></b>!</div>
    </div>
    <div class="col-md-4 col-12 text-md-end text-center">
      <span class="badge bg-primary p-3 fs-6"><i class="bi bi-person-circle me-2"></i>Gudang</span>
    </div>
  </div>

  <!-- Filter tanggal -->
  <div class="row g-3 mb-2 align-items-end">
    <div class="col-12 col-md-6 col-lg-4 mb-2 mb-md-0">
      <label class="form-label mb-1">Filter Tanggal Statistik Produk</label>
      <div class="input-group">
        <input type="date" class="form-control" id="statStart" value="" />
        <span class="input-group-text">s/d</span>
        <input type="date" class="form-control" id="statEnd" value="" />
      </div>
    </div>
    <div class="col-auto d-flex gap-2">
      <button class="btn btn-primary" id="btnFilterStat"><i class="bi bi-funnel"></i> Tampilkan</button>
      <button class="btn btn-outline-secondary" id="btnClearFilter"><i class="bi bi-x-circle"></i> Clear Filter</button>
    </div>
  </div>

  <!-- Row statistik -->
  <div class="row g-3 mb-4" id="statistik-produk-row"></div>

  <!-- Grafik bulanan -->
  <div class="row">
    <div class="col-12">
      <div class="card shadow border-0 mb-4">
        <div class="card-body">
          <h5 class="card-title mb-4"><i class="bi bi-bar-chart-fill me-2"></i>Grafik Produk Terjual per Bulan</h5>
          <div style="height:360px"><canvas id="chartTerjual"></canvas></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Detail Terjual -->
<div class="modal fade" id="modalTerjual" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          Detail Produk Terjual <small class="text-muted" id="label-range"></small>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex gap-2 mb-3">
          <select id="chartType" class="form-select" style="max-width:200px">
            <option value="bar">Bar</option>
            <option value="doughnut">Donut</option>
            <option value="horizontalBar">Bar Horizontal</option>
          </select>
          <button id="btnExportCSV" class="btn btn-outline-secondary">Export CSV</button>
          <button id="btnDownloadJPG" class="btn btn-outline-primary">Download JPG</button>
        </div>
        <div style="height:420px"><canvas id="chartTerjualDetail"></canvas></div>
        <hr class="my-4">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle" id="tblTerjual">
            <thead class="table-light">
              <tr><th>#</th><th>Varian</th><th class="text-end">Qty Terjual</th></tr>
            </thead>
            <tbody></tbody>
            <tfoot>
              <tr class="table-light"><th colspan="2" class="text-end">Total</th><th class="text-end" id="grandTotal">0</th></tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Libraries -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script>
  // ==== Plugin background putih agar export JPG tidak hitam ====
  const CanvasBgPlugin = {
    id: 'canvas_white_bg',
    beforeDraw(chart, args, opts) {
      const {ctx, width, height} = chart;
      ctx.save();
      ctx.globalCompositeOperation = 'destination-over';
      ctx.fillStyle = opts?.color || '#ffffff';
      ctx.fillRect(0, 0, width, height);
      ctx.restore();
    }
  };
  if (window.Chart) { Chart.register(CanvasBgPlugin, ChartDataLabels); }

  // ===== util tanggal default (bulan berjalan)
  function setDefaultStatDate() {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    document.getElementById('statEnd').value = `${yyyy}-${mm}-${dd}`;
    document.getElementById('statStart').value = `${yyyy}-${mm}-01`;
  }

  // ===== load statistik kartu
  function loadStatistikProduk(start = '', end = '') {
    let url = 'get-produk-statistik-new.php';
    const params = [];
    if (start) params.push('start=' + encodeURIComponent(start));
    if (end) params.push('end=' + encodeURIComponent(end));
    if (params.length) url += '?' + params.join('&');

    fetch(url).then(r => r.json()).then(res => {
      if (res.status !== 'success') return;

      const d = res.data;
      const stats = [
        {label:'Pending',     value:d.pending,      color:'warning', icon:'clock-history'},
        {label:'Di Gudang',   value:d.di_gudang,    color:'primary', icon:'box-seam'},
        {label:'Di SPG',      value:d.di_spg,       color:'info',    icon:'person-badge'},
        {label:'Di Toko',     value:d.di_toko,      color:'success', icon:'shop'},
        {label:'Collecting',  value:d.di_collecting,color:'secondary', icon:'truck'},
        {label:'Terjual',     value:d.terjual,      color:'danger',  icon:'cart-check'},
      ];

      document.getElementById('statistik-produk-row').innerHTML = stats.map(s => `
        <div class="col-6 col-md-4 col-lg-2">
          <div class="card border-0 shadow-sm h-100 animate__animated animate__fadeInUp ${s.label==='Terjual' ? 'cursor-pointer' : ''}"
               ${s.label==='Terjual' ? 'id="card-terjual"' : ''}>
            <div class="card-body text-center">
              <div class="mb-2">
                <span class="rounded-circle bg-${s.color} bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                  <i class="bi bi-${s.icon} fs-3 text-${s.color}"></i>
                </span>
              </div>
              <div class="fw-bold fs-4 mb-1 text-${s.color}">${s.value}</div>
              <div class="text-muted small">${s.label}</div>
            </div>
          </div>
        </div>
      `).join('');
    });
  }

  // ===== inisialisasi
  setDefaultStatDate();
  loadStatistikProduk(document.getElementById('statStart').value, document.getElementById('statEnd').value);

  document.getElementById('btnFilterStat').addEventListener('click', () => {
    loadStatistikProduk(statStart.value, statEnd.value);
  });
  document.getElementById('btnClearFilter').addEventListener('click', () => {
    setDefaultStatDate();
    loadStatistikProduk(statStart.value, statEnd.value);
  });

  // ===== grafik terjual bulanan
  fetch('get-produk-terjual-bulanan.php')
    .then(res => res.json())
    .then(res => {
      if (res.status !== 'success') return;
      const bulanIndo = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
      const labels = res.data.map(x => {
        const [tahun, bulan] = x.bulan.split('-');
        return bulanIndo[parseInt(bulan,10)-1] + ' ' + tahun;
      });
      const data = res.data.map(x => x.total_terjual);
      const maxVal = Math.max(0, ...data);
      new Chart(document.getElementById('chartTerjual').getContext('2d'), {
        type: 'bar',
        data: { labels, datasets: [{
          label:'Produk Terjual', data,
          backgroundColor:'rgba(54,162,235,0.7)', borderColor:'#36a2eb', borderWidth:2, borderRadius:8, maxBarThickness:40
        }]},
        options: {
          responsive:true,
          plugins:{
            legend:{display:false}, title:{display:false},
            canvas_white_bg: { color:'#ffffff' }   // <- latar putih saat export
          },
          scales:{ 
            x:{grid:{display:false}},
            y:{beginAtZero:true, grid:{color:'#eee'}, suggestedMax: maxVal + Math.ceil(maxVal*0.2) }
          }
        }
      });
    });

  // ===== DETAIL TERJUAL (Modal + Chart + Tabel + CSV + Download JPG)
  (function(){
    let chartDetail;

    const elModal  = document.getElementById('modalTerjual');
    const elCanvas = document.getElementById('chartTerjualDetail');
    const elType   = document.getElementById('chartType');
    const elTblTbd = document.querySelector('#tblTerjual tbody');
    const elGrand  = document.getElementById('grandTotal');
    const elRange  = document.getElementById('label-range');
    const btnCSV   = document.getElementById('btnExportCSV');
    const btnJPG   = document.getElementById('btnDownloadJPG');

    function currentFilters(){ return { start: statStart.value || '', end: statEnd.value || '' }; }

    async function loadDetail(){
      const p = new URLSearchParams(currentFilters());
      const r = await fetch('get-produk-terjual-detail.php?' + p.toString());
      if (!r.ok) throw new Error('Gagal memuat data');
      return r.json();
    }

    function fillTable(rows,total){
      elTblTbd.innerHTML = '';
      rows.forEach((r,i)=>{
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${i+1}</td><td>${r.nama_barang}</td><td class="text-end">${r.total_qty}</td>`;
        elTblTbd.appendChild(tr);
      });
      elGrand.textContent = total;
    }

    function makePalette(n){
      const colors = [];
      for (let i=0;i<n;i++){
        const hue = Math.round((360/n) * i);
        colors.push({ bg:`hsla(${hue},65%,60%,0.55)`, bd:`hsl(${hue},65%,38%)` });
      }
      return colors;
    }

    function buildCfg(labels,data,type,maxVal){
      const pal = makePalette(labels.length);
      const isHBar = (type==='horizontalBar');
      const isDonut= (type==='doughnut');

      return {
        type: isHBar ? 'bar' : type,
        data: {
          labels,
          datasets: [{
            label:'Terjual',
            data,
            backgroundColor: pal.map(p=>p.bg),
            borderColor: pal.map(p=>p.bd),
            borderWidth:2,
            borderRadius: isDonut ? 0 : 10,
            maxBarThickness:44
          }]
        },
        options: {
          responsive:true,
          maintainAspectRatio:false,
          layout:{ padding:{ right:24 } },
          plugins:{
            legend:{ display:isDonut },
            tooltip:{ enabled:true },
            datalabels:{
              color:'#1f2937', font:{ weight:'600' },
              anchor: isDonut? 'center':'end',
              align:  isDonut? 'center':'end',
              clamp:true, clip:false, offset: isDonut? 0:6,
              formatter:(v,ctx)=>{
                if(isDonut){
                  const ds = ctx.chart.data.datasets[0].data;
                  const sum = ds.reduce((a,b)=>a+b,0) || 1;
                  const pct = Math.round((v/sum)*100);
                  return `${v} (${pct}%)`;
                }
                return v;
              }
            },
            // <-- kunci: cat putih di bawah chart sebelum export
            canvas_white_bg:{ color:'#ffffff' }
          },
          indexAxis: isHBar ? 'y' : 'x',
          scales: isDonut ? {} : {
            x:{ beginAtZero:true, grid:{ color:'#eef1f5' }, suggestedMax: maxVal + Math.max(1, Math.ceil(maxVal*0.2)) },
            y:{ beginAtZero:true, grid:{ color:'#f3f5f8' }, ticks:{ precision:0 } }
          }
        }
      };
    }

    async function renderDetail(){
      const p = await loadDetail();
      elRange.textContent = p.rangeLabel ? ' ' + p.rangeLabel : '';
      fillTable(p.rows, p.total);

      const maxVal = Math.max(0, ...(p.data || [0]));
      if (chartDetail) chartDetail.destroy();
      chartDetail = new Chart(elCanvas.getContext('2d'), buildCfg(p.labels, p.data, elType.value, maxVal));
    }

    // open modal from Terjual card
    document.addEventListener('click', e=>{
      if (e.target.closest('#card-terjual')) {
        const modal = new bootstrap.Modal(elModal);
        modal.show();
        renderDetail();
      }
    });

    // ganti tipe grafik
    elType.addEventListener('change', renderDetail);

    // export CSV
    btnCSV.addEventListener('click', async ()=>{
      const p = await loadDetail();
      const header = ['No','Varian','Qty Terjual'];
      const body = p.rows.map((r,i)=>[i+1,r.nama_barang,r.total_qty]);
      const all = [header,...body];
      const csv = all.map(row=>row.map(v=>`"${String(v).replace(/"/g,'""')}"`).join(',')).join('\r\n');
      const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
      const url = URL.createObjectURL(blob);
      const a = Object.assign(document.createElement('a'), {href:url, download:'terjual_per_varian.csv'});
      document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
    });

    // download JPG (background sudah putih via plugin)
    btnJPG.addEventListener('click', ()=>{
      if (!chartDetail) return;
      const url = chartDetail.toBase64Image('image/jpeg', 1);
      const a = Object.assign(document.createElement('a'), {href:url, download:'grafik-terjual.jpg'});
      document.body.appendChild(a); a.click(); a.remove();
    });
  })();

  // animate.css (opsional)
  const animateCss = document.createElement('link');
  animateCss.rel = 'stylesheet';
  animateCss.href = 'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css';
  document.head.appendChild(animateCss);
</script>

</div> <!-- tutup .content -->
</body>
</html>
