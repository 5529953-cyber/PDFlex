<?php $layout = $layout ?? 'app'; ?>
<?php if ($layout === 'app'): ?>
    </main>
  </div>
<?php else: ?>
  </div>
<?php endif; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
