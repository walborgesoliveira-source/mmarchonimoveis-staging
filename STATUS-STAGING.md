# Status do staging

Data do registro: 2026-04-29T03:30:31+02:00

Este ambiente continua reservado para testes:

```text
staging.mmarchonimoveis.com.br -> mmarchonimoveis_staging_wp:80
```

O site publico `www.mmarchonimoveis.com.br` foi preparado para apontar para outro ambiente:

```text
www.mmarchonimoveis.com.br -> mmarchonimoveis_wp:80
```

Nao misturar os fluxos:

1. editar no staging
2. testar
3. validar
4. aplicar no site publico

Atualizacao pos-propagacao:

- DNS propagado para `5.189.152.8`.
- SSL do `www.mmarchonimoveis.com.br` ativado no Nginx Proxy Manager.
- Certificado publico cobre `mmarchonimoveis.com.br` e `www.mmarchonimoveis.com.br`.
- Staging validado e continua online em HTTPS.
