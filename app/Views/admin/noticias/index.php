<div class="d-flex justify-content-between align-items-center mb-4">
    <h3><?= $title ?></h3>
    <a href="<?= url('/admin/noticia/add') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nova Notícia
    </a>
</div>

<div class="card bg-dark text-white border-secondary shadow">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 80px;">Img</th>
                        <th>Título</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dados)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Nenhuma notícia encontrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($dados as $item): ?>
                            <tr>
                                <td>
                                    <?php if ($item['imagem']): ?>
                                        <img src="<?= url('/' . $item['imagem']) ?>" style="width: 50px; height: 40px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                        <div class="bg-secondary text-center rounded" style="width: 50px; height: 40px; line-height: 40px;"><i class="bi bi-image"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($item['titulo']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $item['ativo'] ? 'success' : 'secondary' ?>">
                                        <?= $item['ativo'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('/admin/noticia/edit?id=' . $item['id']) ?>" class="btn btn-outline-info">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= url('/admin/noticia/delete?id=' . $item['id']) ?>" 
                                           class="btn btn-outline-danger" 
                                           onclick="return confirm('Tem certeza que deseja excluir esta notícia?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
