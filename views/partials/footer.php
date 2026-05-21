<!-- views/partials/footer.php -->
    </div><!-- /.main-content -->
  </div><!-- /.app-wrapper -->

  <footer class="app-footer mt-auto">
    <div class="container-fluid px-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
        <span class="text-muted small">
          &copy; <?= date('Y') ?> <strong><?= APP_NAME ?></strong> — PTIT
        </span>
        <span class="text-muted small">
          <i class="bi bi-code-slash me-1"></i>Phiên bản <?= APP_VERSION ?>
          &nbsp;|&nbsp;
          <i class="bi bi-clock me-1"></i><?= date('H:i d/m/Y') ?>
        </span>
      </div>
    </div>
  </footer>

</div><!-- /.d-flex flex-column min-vh-100 -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>


