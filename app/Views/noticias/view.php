<div class="row mb-4">
    <div class="col">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= url('/inicio') ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= url('/noticias') ?>">Notícias</a></li>
                <li class="breadcrumb-item active text-muted" aria-current="page">Visualizar</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-lg-10 mx-auto">
        <article class="weather-card overflow-hidden fade-in-up shadow-lg">
            <?php if ($noticia['imagem']): ?>
                <div class="detail-img-container" style="max-height: 450px; overflow: hidden;">
                    <img src="<?= url('/' . $noticia['imagem']) ?>" alt="<?= htmlspecialchars($noticia['titulo']) ?>" class="w-100" style="object-fit: cover;">
                </div>
            <?php endif; ?>
            
            <div class="card-body p-4 p-md-5">
                <small class="text-primary d-block mb-2 fw-bold">
                    <i class="bi bi-calendar3"></i> <?= date('d/m/Y H:i', strtotime($noticia['data_noticia'])) ?>
                </small>
                <h2 class="text-dark mb-4 display-6 fw-bold"><?= htmlspecialchars($noticia['titulo']) ?></h2>
                
                <p class="lead text-accent-warm border-start border-3 border-warning ps-3 mb-5">
                    <?= htmlspecialchars($noticia['resumo']) ?>
                </p>
                
                <div class="news-content text-secondary" style="line-height: 1.8; font-size: 1.1rem;">
                    <?= nl2br(htmlspecialchars($noticia['conteudo'])) ?>
                </div>
                
                <hr class="my-5 border-secondary">
                
                <div class="d-flex justify-content-between align-items-center">
                    <a href="<?= url('/noticias') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Voltar para Notícias
                    </a>
                    <div class="share-links">
                        <span class="small text-muted me-2">Compartilhar:</span>
                        <a href="#" class="btn btn-sm btn-dark text-primary"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="btn btn-sm btn-dark text-primary"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="btn btn-sm btn-dark text-primary"><i class="bi bi-whatsapp"></i></a>
                    </div>
                </div>
            </div>
        </article>
    </div>
</div>

<style>
.news-content {
    white-space: pre-line;
}
.detail-img-container img {
    border-radius: 12px 12px 0 0;
}
</style>
