<!-- GuardReport Admin Scripts Footer | File: layouts/admin-scripts.php -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<script>
if (typeof Swal !== 'undefined') {
  const _swal = Swal.mixin({
    customClass:{popup:'gr-swal-popup'},buttonsStyling:false,
    showClass:{popup:'animate__animated animate__fadeInDown animate__faster'},
    hideClass:{popup:'animate__animated animate__fadeOutUp animate__faster'},
  });
  window.grSuccess = (t,m,cb) => _swal.fire({icon:'success',title:t,text:m,confirmButtonText:'OK',customClass:{popup:'gr-swal-popup',confirmButton:'gr-btn gr-btn-primary'}}).then(r=>{if(r.isConfirmed&&cb)cb();});
  window.grError   = (t,m)    => _swal.fire({icon:'error',  title:t||'Error',text:m,confirmButtonText:'Close',customClass:{popup:'gr-swal-popup',confirmButton:'gr-btn gr-btn-danger'}});
  window.grConfirm = (t,m,cb,ic='warning') => _swal.fire({icon:ic,title:t,text:m,showCancelButton:true,confirmButtonText:'Confirm',cancelButtonText:'Cancel',customClass:{popup:'gr-swal-popup',confirmButton:'gr-btn gr-btn-danger',cancelButton:'gr-btn gr-btn-outline'}}).then(r=>{if(r.isConfirmed)cb();});
  window.grLoading = t => Swal.fire({title:t||'Processing…',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
}
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('[data-fill]').forEach(el=>{
    const t=el.getAttribute('data-fill'); el.style.width='0';
    requestAnimationFrame(()=>{ el.style.transition='width 0.9s cubic-bezier(.4,0,.2,1)'; el.style.width=t; });
  });
});
</script>
<?php if (!empty($pageScripts)) echo $pageScripts; ?>
</main>
</body>
</html>