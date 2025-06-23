<?php
// Incluir configurações
require_once 'includes/config.php';

// Incluir cabeçalho
$titulo_pagina = "Ajuda";
include 'includes/header.php';
?>

<section class="help-container">
    <h1>Central de Ajuda</h1>

    <div class="faq">
        <h2>Perguntas Frequentes (FAQ)</h2>
        
        <div class="faq-item">
            <h3>Como funciona o aluguel de vestidos?</h3>
            <p>O processo é simples: escolha o vestido desejado, selecione o tamanho e as datas de aluguel. Adicione ao carrinho e finalize a compra. O vestido será entregue no endereço cadastrado e deverá ser devolvido na data combinada.</p>
        </div>

        <div class="faq-item">
            <h3>Qual o prazo de entrega?</h3>
            <p>O prazo de entrega varia conforme a sua localização. Geralmente, entregamos em até 4 dias úteis antes da data do evento para que você tenha tempo de experimentar.</p>
        </div>

        <div class="faq-item">
            <h3>Como faço para devolver o vestido?</h3>
            <p>A devolução é fácil. Utilize a embalagem original e a etiqueta de devolução pré-paga que acompanha o pedido. Entregue o pacote em uma agência dos Correios ou solicite a coleta, dependendo da sua região.</p>
        </div>

        <div class="faq-item">
            <h3>Posso fazer ajustes no vestido?</h3>
            <p>Pequenos ajustes temporários, como o uso de alfinetes ou fita dupla-face, são permitidos. No entanto, alterações permanentes como cortes ou costuras não são autorizadas.</p>
        </div>

         <div class="faq-item">
            <h3>O que acontece se o vestido for danificado?</h3>
            <p>Pequenos danos decorrentes do uso normal (como fios puxados ou pequenas manchas) são cobertos pela taxa de seguro inclusa no aluguel. Danos maiores podem estar sujeitos a taxas adicionais. Consulte nossos Termos de Uso para mais detalhes.</p>
        </div>
    </div>

    <div class="contact">
        <h2>Entre em Contato</h2>
        <p>Se sua dúvida não foi respondida acima, entre em contato conosco:</p>
        <ul>
            <li><strong>E-mail:</strong> contato@meuvestido.com</li>
            <li><strong>Telefone/WhatsApp:</strong> (XX) XXXX-XXXX</li>
            <li><strong>Horário de Atendimento:</strong> Segunda a Sexta, das 9h às 18h</li>
        </ul>
    </div>
</section>

<?php
// Incluir rodapé
include 'includes/footer.php';
?>
