---
title: Informe de transparencia de seguridad de aplicaciones
date: 2026-03-31
author: Seguridad de PayCal
tags: security, appsec, billing_hardening
---

## Metadatos del informe

◆ Fecha: 2026-03-31
◆ Alcance: Manejo de solicitudes, redirecciones, protecciones de API y límites de confianza
◆ Referencia: Auditoría de seguridad interna (2026-03-31)

## Descripción general

Recientemente completamos una revisión de seguridad de aplicaciones enfocada en vectores de ataque del mundo real que afectan a las aplicaciones web modernas. Este esfuerzo priorizó la **reducción práctica de riesgos** sin interrumpir el comportamiento normal del producto.

Este documento describe lo que se identificó, lo que se cambió y cómo abordamos la seguridad continua.

### Evento desencadenante e informes externos

Fuimos alertados hoy por informes confirmados sobre la compromisión del paquete npm Axios. Esa alerta desencadenó directamente este ciclo completo de auditoría y barrido interno del sistema.

Referencias técnicas externas:
◆ BleepingComputer: [Hackers comprometen el paquete npm Axios para distribuir malware multiplataforma](https://www.bleepingcomputer.com/news/security/hackers-compromise-axios-npm-package-to-drop-cross-platform-malware/)
◆ The Hacker News: [El ataque a la cadena de suministro de Axios distribuye RAT multiplataforma a través de cuenta npm comprometida](https://thehackernews.com/2026/03/axios-supply-chain-attack-pushes-cross.html)
◆ The Register: [Explosión en la cadena de suministro: paquete npm popular con backdoor para instalar un RAT](https://www.theregister.com/2026/03/31/axios_npm_backdoor_rat/)

## Resultados clave

Identificamos y remediamos tres riesgos de seguridad significativos:

◆ Manejo de redirecciones: vector de redirección abierta (corregido)
◆ Confianza en cabeceras: envenenamiento Host/cabecera (corregido)
◆ Protección de API: comprobaciones CSRF faltantes (corregido)

## Lo que corregimos

### 1) Seguridad en redirecciones (cambio de idioma)

**Problema**
Las redirecciones dependían de `HTTP_REFERER`, que puede estar ausente o ser manipulado. Esto crea posibles cadenas de phishing usando dominios de confianza.

**Resolución**
◆ Validación estricta del host impuesta
◆ Solo se permiten redirecciones internas o del mismo origen
◆ Fallback predeterminado a `/` cuando la validación falla

**Resultado**
Las redirecciones ahora están **explícitamente limitadas a orígenes de confianza**.

### 2) Límites de confianza de cabeceras (flujos de facturación)

**Problema**
Las cabeceras reenviadas (p. ej. host/proto) influían en la lógica de origen sin verificar la fuente de la solicitud. Una mala configuración podría permitir la manipulación del host.

**Resolución**
◆ Se introdujo el **control de proxy de confianza**
◆ Las cabeceras reenviadas solo se aceptan de infraestructura conocida
◆ Todos los demás casos vuelven al origen canónico de la aplicación

**Resultado**
El manejo del origen ahora es **determinista y resistente al spoofing de cabeceras**.

### 3) Protección CSRF (acciones de facturación)

**Problema**
Los endpoints de facturación autenticados carecían de validación CSRF. Esto exponía los endpoints de mutación a falsificación de solicitudes entre sitios bajo sesiones válidas.

**Resolución**
◆ Validación CSRF aplicada a todas las mutaciones de facturación
◆ Lógica de verificación de tokens centralizada
◆ El frontend envía tokens de forma consistente

**Resultado**
Todas las operaciones de facturación que modifican estado ahora requieren **solicitudes explícitamente iniciadas por el usuario**.

## Revisión adicional

### Superficies de ejecución de comandos

Revisamos rutas de código que contienen primitivas de ejecución (p. ej. shell/exec).

**Estado actual**
◆ Sin exposición activa a través de controladores o rutas públicas
◆ Sin evidencia de invocación en tiempo de ejecución en rutas de solicitud

**Posición**
◆ Tratar como **herramientas internas no públicas únicamente**
◆ Candidato para eliminación o aislamiento futuro

## Verificación

Todos los cambios fueron validados mediante:

◆ Lint de PHP en archivos modificados
◆ Diagnósticos estáticos del editor
◆ Inspección manual de flujos de solicitudes

No se introdujeron problemas de sintaxis o en tiempo de ejecución.

## Principios de seguridad aplicados

Este refuerzo reafirma algunos principios fundamentales:

◆ **Denegación por defecto** sobre confianza implícita
◆ **Límites de confianza explícitos** (p. ej. proxies, orígenes)
◆ **Validación en cada punto de entrada externo**
◆ **Controles de seguridad centralizados** sobre comprobaciones dispersas

## Lo que esto significa para los usuarios

◆ Riesgo reducido de phishing por abuso de redirecciones
◆ Garantías más sólidas en torno a las acciones de facturación
◆ Integridad mejorada en el manejo de solicitudes y validación de origen

No se requiere ninguna acción por parte de los usuarios.

## Trabajo en curso

Tratamos la seguridad como algo continuo. Los próximos pasos incluyen:

◆ Pruebas de integración: comportamiento de validación de redirecciones
◆ Pruebas de integración: aplicación de CSRF en endpoints
◆ Pruebas de integración: manejo de límites de confianza de proxy
◆ Escaneos periódicos: sumideros de redirecciones
◆ Escaneos periódicos: regresiones de confianza de cabeceras
◆ Clasificación interna de rutas de alto riesgo

## Archivos actualizados

◆ `html/lang/index.php`
◆ `html/src/Controllers/BillingController.php`
◆ `html/js/core/billing.js`

## Nota de cierre

Este esfuerzo se centró en eliminar **rutas de explotación realistas**, no casos extremos teóricos. Continuaremos priorizando cambios que mejoren significativamente la seguridad preservando la fiabilidad del producto.
