<?php
$tituloNoticia = (string) $noticia['titulo'];
$linkNoticia = $urlCompartilhar ?? '';
$textoCompartilhar = $tituloNoticia . ' — ' . $linkNoticia;
?>
<div class="row g-4 mb-5">
    <div class="col-lg-10 mx-auto">
        <nav aria-label="Você está em" class="mb-3">
            <ol class="breadcrumb breadcrumb-noticia mb-0">
                <li class="breadcrumb-item"><a href="<?= url('/inicio') ?>"><i class="bi bi-house-door" aria-hidden="true"></i> Início</a></li>
                <li class="breadcrumb-item"><a href="<?= url('/noticias') ?>">Notícias</a></li>
                <li class="breadcrumb-item active" aria-current="page"><span class="breadcrumb-titulo"><?= htmlspecialchars($tituloNoticia) ?></span></li>
            </ol>
        </nav>

        <article class="weather-card overflow-hidden fade-in-up shadow-lg">
            <header class="p-4 p-md-5 pb-4 pb-md-4">
                <small class="text-primary d-block mb-2 fw-bold">
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    <time datetime="<?= date('c', strtotime($noticia['data_noticia'])) ?>"><?= date('d/m/Y H:i', strtotime($noticia['data_noticia'])) ?></time>
                </small>
                <h1 class="text-dark mb-3 display-6 fw-bold"><?= htmlspecialchars($tituloNoticia) ?></h1>

                <div class="share-links" role="group" aria-label="Compartilhar esta notícia">
                    <span class="small text-muted me-1">Compartilhar:</span>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($linkNoticia) ?>" class="btn btn-sm btn-share btn-share-facebook" target="_blank" rel="noopener noreferrer" aria-label="Compartilhar no Facebook (abre em nova janela)" title="Facebook"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                    <a href="https://twitter.com/intent/tweet?url=<?= rawurlencode($linkNoticia) ?>&amp;text=<?= rawurlencode($tituloNoticia) ?>" class="btn btn-sm btn-share btn-share-x" target="_blank" rel="noopener noreferrer" aria-label="Compartilhar no X (abre em nova janela)" title="X"><i class="bi bi-twitter-x" aria-hidden="true"></i></a>
                    <a href="https://api.whatsapp.com/send?text=<?= rawurlencode($textoCompartilhar) ?>" class="btn btn-sm btn-share btn-share-whatsapp" target="_blank" rel="noopener noreferrer" aria-label="Compartilhar no WhatsApp (abre em nova janela)" title="WhatsApp"><i class="bi bi-whatsapp" aria-hidden="true"></i></a>
                    <button type="button" class="btn btn-sm btn-share btn-share-link" data-copiar-link="<?= htmlspecialchars($linkNoticia) ?>" aria-label="Copiar o link da notícia" title="Copiar link"><i class="bi bi-link-45deg" aria-hidden="true"></i></button>
                    <span class="small text-success ms-1 share-copiado" role="status" aria-live="polite"></span>
                </div>
            </header>

            <?php if ($noticia['imagem']): ?>
                <div class="detail-img-container" style="max-height: 450px; overflow: hidden;">
                    <img src="<?= url('/' . $noticia['imagem']) ?>" alt="<?= htmlspecialchars($tituloNoticia) ?>" class="w-100" style="object-fit: cover;">
                </div>
            <?php endif; ?>

            <div class="card-body p-4 p-md-5">
                <p class="lead text-accent-warm border-start border-3 border-warning ps-3 mb-5">
                    <?= htmlspecialchars($noticia['resumo']) ?>
                </p>

                <div class="news-content text-secondary" style="line-height: 1.8; font-size: 1.1rem;">
                    <?= nl2br(htmlspecialchars($noticia['conteudo'])) ?>
                </div>

                <hr class="my-5 border-secondary">

                <a href="<?= url('/noticias') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Voltar para Notícias
                </a>
            </div>
        </article>
    </div>
</div>

<style>
.news-content {
    white-space: pre-line;
}
.breadcrumb-noticia {
    font-size: 0.875rem;
    flex-wrap: nowrap;
    min-width: 0;
}
.breadcrumb-noticia .breadcrumb-item { white-space: nowrap; flex-shrink: 0; }
.breadcrumb-noticia .breadcrumb-item a {
    color: var(--text-muted, #6c757d);
    text-decoration: none;
}
.breadcrumb-noticia .breadcrumb-item a:hover { color: var(--bs-primary, #0d6efd); text-decoration: underline; }
.breadcrumb-noticia .breadcrumb-item.active {
    min-width: 0;
    flex: 1 1 auto;
    color: var(--text-dark, #212529);
    font-weight: 600;
}
.breadcrumb-noticia .breadcrumb-titulo {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
}
.share-links {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
}
.btn-share {
    width: 34px;
    height: 34px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: #fff;
    border: none;
    font-size: 1rem;
}
.btn-share:hover, .btn-share:focus-visible { color: #fff; filter: brightness(1.12); }
.btn-share-facebook { background: #1877f2; }
.btn-share-x { background: #000; }
.btn-share-whatsapp { background: #25d366; }
.btn-share-link { background: #6c757d; }
</style>
<script>
document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('[data-copiar-link]');
    if (!btn) return;
    var link = btn.getAttribute('data-copiar-link');
    var aviso = btn.parentElement.querySelector('.share-copiado');
    var ok = function () { if (aviso) { aviso.textContent = 'Link copiado!'; setTimeout(function () { aviso.textContent = ''; }, 2500); } };
    var reserva = function () {
        // navegador sem clipboard (ou http): copia por um campo temporário
        var campo = document.createElement('textarea');
        campo.value = link; campo.setAttribute('readonly', ''); campo.style.position = 'fixed'; campo.style.opacity = '0';
        document.body.appendChild(campo); campo.select();
        try { document.execCommand('copy'); ok(); } catch (err) { window.prompt('Copie o link:', link); }
        campo.remove();
    };
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(link).then(ok, reserva);
    } else {
        reserva();
    }
});
</script>
