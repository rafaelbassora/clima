<?php
$cidadeNome = $config['cidade']['nome'] ?? 'Pereira Barreto';
$appNomeSistema = $config['app']['nome_sistema'] ?? 'Clima';
$dominio = $config['app']['dominio'] ?? '';
// contato do encarregado (DPO): config['privacidade']['contato_email'] e/ou ['contato_url'], se configurados
$contatoEmail = $config['privacidade']['contato_email'] ?? '';
$contatoUrl = $config['privacidade']['contato_url'] ?? '';
$atualizadaEm = '04/11/2026';
?>
<div class="row mb-5">
    <div class="col-lg-10 mx-auto">
        <div class="weather-card p-4 p-md-5 fade-in-up">
            <h2 class="display-6 fw-bold text-primary mb-4 text-center">Política de Privacidade</h2>
            <p class="text-muted text-center mb-5">Portal <?= htmlspecialchars($appNomeSistema) ?> · atualizada em <?= $atualizadaEm ?></p>

            <div class="terms-content text-secondary" style="line-height: 1.8;">

                <section class="mb-5">
                    <h4 class="text-dark fw-bold mb-3">1. Quem cuida dos seus dados</h4>
                    <p>Este portal é mantido pela Prefeitura da Estância Turística de <?= htmlspecialchars($cidadeNome) ?>, que é a
                        controladora dos dados pessoais tratados aqui, nos termos da Lei Geral de Proteção de Dados
                        (Lei nº 13.709/2018 — LGPD).</p>
                </section>

                <section class="mb-5">
                    <h4 class="text-dark fw-bold mb-3">2. Quais dados coletamos</h4>
                    <ul>
                        <li><strong>Para consultar o portal:</strong> nenhum cadastro é necessário. Se você permitir, coletamos
                            estatísticas de uso pelo Google Analytics (páginas visitadas, tipo de aparelho e navegador, região
                            aproximada), sem identificar você pelo nome.</li>
                        <li><strong>Pluviômetro Cidadão (cadastro opcional):</strong> nome (e nome social, se preferir), CPF ou CNPJ,
                            data de nascimento, sexo, celular, e-mail, CEP, bairro e cidade, senha (guardada de forma criptografada)
                            e as medições de chuva que você lançar.</li>
                        <li><strong>Área administrativa:</strong> nome, e-mail e senha dos servidores com acesso ao painel.</li>
                    </ul>
                </section>

                <section class="mb-5">
                    <h4 class="text-dark fw-bold mb-3">3. Para que usamos</h4>
                    <ul>
                        <li>Fazer o portal funcionar e manter sua sessão quando você entra na sua conta.</li>
                        <li>Identificar e moderar as estações do Pluviômetro Cidadão, entrar em contato sobre o cadastro e permitir
                            a recuperação de senha por e-mail.</li>
                        <li>Entender como o portal é usado para melhorá-lo (só com o seu consentimento para cookies de estatística).</li>
                        <li>Proteger os formulários contra robôs e uso indevido.</li>
                    </ul>
                    <p>Publicamente, cada estação cidadã aparece <strong>apenas com um código e o bairro</strong> — nome, documentos,
                        endereço e contatos nunca são divulgados.</p>
                </section>

                <section class="mb-5">
                    <h4 class="text-dark fw-bold mb-3">4. Base legal</h4>
                    <p>Tratamos dados para a execução de políticas públicas e serviços de utilidade pública (art. 7º, III, e
                        art. 23 da LGPD), e com base no seu <strong>consentimento</strong> para o cadastro no Pluviômetro Cidadão
                        e para os cookies de estatística (art. 7º, I). Você pode retirar o consentimento a qualquer momento.</p>
                </section>

                <section class="mb-5">
                    <h4 class="text-dark fw-bold mb-3">5. Cookies</h4>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr><th>Tipo</th><th>Para que serve</th><th>Precisa de permissão?</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>Sessão</td><td>Manter você conectado na sua conta (Pluviômetro Cidadão ou painel)</td><td>Não — essencial</td></tr>
                                <tr><td>Preferência de cookies</td><td>Lembrar se você aceitou ou recusou os cookies de estatística</td><td>Não — essencial</td></tr>
                                <tr><td>Segurança (Cloudflare Turnstile)</td><td>Verificar que quem preenche os formulários de login e cadastro não é um robô</td><td>Não — essencial</td></tr>
                                <tr><td>Estatística (Google Analytics)</td><td>Contar visitas e entender o uso do portal</td><td><strong>Sim</strong> — só com “Aceitar”</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <p>O portal também pode guardar no seu aparelho uma cópia das páginas visitadas para funcionar sem internet
                        (aplicativo instalável) e lembrar suas preferências de acessibilidade (tamanho do texto e alto
                        contraste), sem enviar nada ao servidor. Dá para mudar sua escolha sobre cookies a qualquer momento em
                        <a href="#" data-preferencias-cookies>Preferências de cookies</a>.</p>
                </section>

                <section class="mb-5">
                    <h4 class="text-dark fw-bold mb-3">6. Compartilhamento</h4>
                    <p>Não vendemos nem cedemos seus dados. Alguns serviços de terceiros participam do funcionamento do portal,
                        apenas para as finalidades acima: Google (estatísticas, se você aceitar), Cloudflare (proteção contra robôs),
                        VLibras do Governo Federal (tradução para Libras) e o serviço de e-mail usado para recuperação de senha. Os dados também podem ser fornecidos a autoridades
                        quando houver obrigação legal.</p>
                </section>

                <section class="mb-5">
                    <h4 class="text-dark fw-bold mb-3">7. Segurança e por quanto tempo guardamos</h4>
                    <p>Os dados ficam em servidores da Prefeitura, com acesso restrito e senhas criptografadas. Os dados de cadastro
                        são mantidos enquanto a conta existir ou pelo prazo exigido em lei. As medições de chuva e as leituras das
                        estações são dados meteorológicos de interesse público e podem continuar publicadas sem identificação de quem as lançou.</p>
                </section>

                <section class="mb-5">
                    <h4 class="text-dark fw-bold mb-3">8. Seus direitos</h4>
                    <p>Você pode pedir a confirmação de que tratamos seus dados, acesso, correção, anonimização ou exclusão, informações
                        sobre compartilhamento e a revogação do consentimento (art. 18 da LGPD). Parte disso você mesmo faz no
                        painel do Pluviômetro Cidadão (editar perfil); para o restante, use o contato abaixo.</p>
                </section>

                <section class="mb-5">
                    <h4 class="text-dark fw-bold mb-3">9. Contato</h4>
                    <p>
                        Dúvidas ou pedidos sobre seus dados:
                        <?php if ($contatoEmail): ?>
                            <a href="mailto:<?= htmlspecialchars($contatoEmail) ?>"><?= htmlspecialchars($contatoEmail) ?></a><?= $contatoUrl ? ' ou ' : '.' ?>
                        <?php endif; ?>
                        <?php if ($contatoUrl): ?>
                            <a href="<?= htmlspecialchars($contatoUrl) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($contatoUrl) ?></a>.
                        <?php endif; ?>
                        <?php if (!$contatoEmail && !$contatoUrl): ?>
                            pelos canais oficiais de atendimento da Prefeitura da Estância Turística de <?= htmlspecialchars($cidadeNome) ?>
                            (Ouvidoria / Protocolo).
                        <?php endif; ?>
                    </p>
                </section>

                <section class="mb-2">
                    <h4 class="text-dark fw-bold mb-3">10. Atualizações</h4>
                    <p>Esta política pode ser atualizada para refletir mudanças no portal ou na lei. A data da última atualização fica
                        no topo da página. Veja também os nossos <a href="<?= url('/termos') ?>">Termos de Uso</a>.</p>
                </section>
            </div>
        </div>
    </div>
</div>
