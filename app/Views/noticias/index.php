<div class="row align-items-center mb-4 g-3">
    <div class="col">
        <h4 class="mb-0">
            <i class="bi bi-newspaper text-primary"></i> Notícias e Comunicados
        </h4>
        <small class="text-muted">Acompanhe as últimas atualizações de <?= htmlspecialchars($config['app']['nome_sistema'] ?? 'Clima') ?></small>
    </div>
</div>

<?php if (empty($noticias)): ?>
    <div class="text-center py-5">
        <div class="mb-3">
            <i class="bi bi-chat-dots" style="font-size: 3rem; color: var(--border-color);"></i>
        </div>
        <h5 class="text-muted">Nenhuma notícia disponível no momento.</h5>
        <a href="<?= url('/inicio') ?>" class="btn btn-primary mt-3">Voltar para Home</a>
    </div>
<?php else: ?>
    <div class="row g-4 mb-5">
        <?php foreach ($noticias as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="weather-card h-100 d-flex flex-column fade-in-up">
                    <a href="<?= url('/noticias/view?id=' . $item['id']) ?>" class="text-decoration-none h-100 d-flex flex-column">
                        <div class="news-img-container">
                            <?php if ($item['imagem']): ?>
                                <img src="<?= url('/' . $item['imagem']) ?>" alt="<?= htmlspecialchars($item['titulo']) ?>">
                            <?php else: ?>
                                <div class="news-placeholder">
                                    <i class="bi bi-image"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-4 d-flex flex-column flex-grow-1">
                            <small class="text-muted mb-2 d-block">
                                <i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($item['data_noticia'])) ?>
                            </small>
                            <h5 class="card-title text-dark mb-3"><?= htmlspecialchars($item['titulo']) ?></h5>
                            <p class="card-text text-muted mb-4 small flex-grow-1">
                                <?= htmlspecialchars(mb_strimwidth($item['resumo'], 0, 120, '...')) ?>
                            </p>
                            <div class="text-primary mt-auto d-flex align-items-center">
                                Ler notícia <i class="bi bi-arrow-right ms-2"></i>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Paginação -->
    <?php if ($pages > 1): ?>
        <nav aria-label="Navegação de notícias" class="mb-5">
            <ul class="pagination pagination-custom justify-content-center">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= url('/noticias?page=' . $i) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<style>
.news-img-container {
    height: 200px;
    overflow: hidden;
    position: relative;
    border-radius: 12px 12px 0 0;
}
.news-img-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}
.news-img-container:hover img {
    transform: scale(1.05);
}
.news-placeholder {
    width: 100%;
    height: 100%;
    background: #2a2d34;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #444;
}
.news-placeholder i {
    font-size: 3rem;
}
.pagination-custom .page-link {
    background-color: var(--card-bg) !important;
    border-color: var(--border-color) !important;
    color: var(--text-muted);
}
.pagination-custom .page-item.active .page-link {
    background-color: var(--primary) !important;
    border-color: var(--primary) !important;
    color: white;
}
</style>
