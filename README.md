<p align="center">
  <a href="https://github.com/getmilpa">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/getmilpa/core/main/art/lockup/milpa-lockup-v-color-dark.svg">
      <img src="https://raw.githubusercontent.com/getmilpa/core/main/art/lockup/milpa-lockup-v-color-light.svg" alt="Milpa" width="300">
    </picture>
  </a>
</p>

# Milpa Live

> **Render-target-agnostic live components** for the Milpa PHP framework — the same
> component definition renders to **web AND terminal**; state, data sources, and an
> event-driven interception seam, no HTML or ANSI in the component itself.

[![CI](https://github.com/getmilpa/live/actions/workflows/ci.yml/badge.svg)](https://github.com/getmilpa/live/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/milpa/live.svg)](https://packagist.org/packages/milpa/live)
[![PHP](https://img.shields.io/badge/php-%E2%89%A5%208.3-777bb4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-Apache--2.0-blue.svg)](LICENSE)
[![Docs](https://img.shields.io/badge/docs-API%20reference-blue.svg)](https://getmilpa.github.io/live/)

`milpa/live` is the render-target-agnostic core of Milpa's live component system: a
component owns its **contract** (props/state schema, declared actions), its **initial
state** (`mount()`), and how it reacts to client-originated actions (`handle()`) — but
never how it turns into markup or terminal output. That's a
[`ComponentRendererInterface`](src/Contracts/Rendering/ComponentRendererInterface.php)'s
job, paired with the component at the call site. One component, any number of renderers.

## Install

```bash
composer require milpa/live
```

## Quick example

A minimal component plus two renderers — one for HTML, one for TUI — sharing the exact
same `mount()`/`handle()` logic:

```php
use Milpa\Live\Contracts\Component\ComponentDefinitionInterface;
use Milpa\Live\Contracts\Rendering\ComponentRendererInterface;
use Milpa\Live\ValueObjects\{
    ComponentContext, ComponentContract, InteractionRequest,
    InteractionResult, RenderRequest, RenderResult, RenderTarget, StateSnapshot,
};

final class CounterComponent implements ComponentDefinitionInterface
{
    public static function contract(): ComponentContract
    {
        return new ComponentContract(name: 'counter', contractVersion: '1.0.0', actions: ['increment' => []]);
    }

    public function mount(array $props, ComponentContext $context): StateSnapshot
    {
        return new StateSnapshot(
            componentId: $context->componentId,
            componentName: 'counter',
            version: '1.0.0',
            data: ['count' => (int) ($props['start'] ?? 0)],
        );
    }

    public function handle(InteractionRequest $request): InteractionResult
    {
        return new InteractionResult(state: new StateSnapshot(
            componentId: $request->state->componentId,
            componentName: $request->state->componentName,
            version: $request->state->version,
            data: ['count' => $request->state->data['count'] + 1],
        ));
    }
}

final class HtmlCounterRenderer implements ComponentRendererInterface
{
    public function supportsTarget(RenderTarget $target): bool
    {
        return $target === RenderTarget::HTML;
    }

    public function render(ComponentDefinitionInterface $component, RenderRequest $request): RenderResult
    {
        $state = $request->state ?? $component->mount($request->props, $request->context);

        return new RenderResult(
            output: sprintf('<button data-count="%d">Count: %d</button>', $state->data['count'], $state->data['count']),
            state: $state,
            format: RenderTarget::HTML,
        );
    }
}

final class TuiCounterRenderer implements ComponentRendererInterface
{
    public function supportsTarget(RenderTarget $target): bool
    {
        return $target === RenderTarget::TUI;
    }

    public function render(ComponentDefinitionInterface $component, RenderRequest $request): RenderResult
    {
        $state = $request->state ?? $component->mount($request->props, $request->context);

        return new RenderResult(output: "[ Count: {$state->data['count']} ]", state: $state, format: RenderTarget::TUI);
    }
}

$component = new CounterComponent();
$context = new ComponentContext('demo-1');

$html = (new HtmlCounterRenderer())->render($component, new RenderRequest(context: $context, target: RenderTarget::HTML));
echo $html->output; // <button data-count="0">Count: 0</button>

$tui = (new TuiCounterRenderer())->render($component, new RenderRequest(context: $context, target: RenderTarget::TUI));
echo $tui->output; // [ Count: 0 ]
```

`CounterComponent` never printed a single tag or escape code — both renderers turned the
exact same `StateSnapshot` into their own output, independently.

## Web + TUI from one component

That's the thesis this package is built around: **a component definition is a pure
description of state and behavior; rendering is a separate, swappable concern.** A
`ComponentRendererInterface` declares which [`RenderTarget`](src/ValueObjects/RenderTarget.php)(s)
it supports (`HTML`, `TUI`, or the forward-looking `ANSI`) and turns a mounted
`StateSnapshot` into output for that target — nothing in `ComponentDefinitionInterface`
ever needs to know which renderer, or how many, will consume it.

`milpa/live` ships the component contracts, the mount/handle lifecycle, data sources, and
the event-driven interception seam (`component.mounting`/`mounted`,
`component.handling`/`handled`, `component.rendering`/`rendered` — see
[`LiveEventEmitter`](src/Events/LiveEventEmitter.php)) — but **no HTML and no ANSI
renderer**. The web surface (`AutocompleteHtmlRenderer` and friends) lives in
`milpa/live-web`; a TUI renderer is a live candidate in the Milpa lab. This package is
the seam both build on, not either surface itself.

## Declared views

A plugin **declares** its view — its components, the client behaviour they need, their CSS —
and the host's single runtime **reconciles** it: one Alpine, one `milpa-live`, one boot, one
endpoint, one signing key per page (greenhouse `decisions/0211`). This package ships the
render-agnostic half of that contract; `milpa/live-web` ships the HTML half (the compiler that
collects assets, `LiveBoot` that emits them once, `MilpaLive.register()` on the client).

- **`ClientAssets`** (`Milpa\Live\ValueObjects\ClientAssets`) — the value: `scripts` and
  `styles` URL lists the plugin serves from its own routes. `merge()`/`with()` deduplicate by URL
  and keep first-seen order; `empty()`, `isEmpty()`, `toArray()`.
- **`DeclaresClientAssets`** (`Milpa\Live\Contracts\Rendering\DeclaresClientAssets`) — a
  sibling of `ComponentRendererInterface` that an **HTML renderer** implements to declare the
  files its output depends on. Never the `ComponentDefinitionInterface`: the component contract
  stays render-target-agnostic, and the TUI renderer of the same component has nothing to declare.
- **`RenderResult::clientAssets()`** — the typed channel a compiler fills by merging every
  declaring renderer's assets (`ClientAssets::merge`, so a shared module is emitted once). Empty
  when no renderer declared any. The legacy string-keyed `RenderResult::$assets` bag is untouched
  and still merged with `array_merge` by compilers.
- **`CompositeComponentRegistry`** (`Milpa\Live\Runtime\CompositeComponentRegistry`) — one
  `ComponentRegistryInterface` over ordered, **labelled** layers (`['host' => …, 'billing' => …]`)
  so one endpoint serves every plugin's components and a cross-component effect resolves across
  them. First layer wins on `has()`/`get()`; `register()` writes to the one layer named writable
  (or throws `LogicException` when none); `names()` is the union in layer order. Shadowing is
  never silent: the same name bound to **different definitions** in two layers (a different class,
  or two instances of a class that carries state) throws `ComponentNameConflictException` at
  construction, naming the component and both layers. The same instance in two layers is fine, and
  so are two instances of a stateless class (no instance property at all). The check is identity
  or statelessness — never a structural compare, which recurses into whatever the component holds
  and turns a collaborator pointing back at it into an uncatchable fatal instead of a named
  exception. The shipped components hold a dispatcher, so two `new TextareaComponent()` under one
  name in two layers are a conflict: a plugin reuses the host's instance or names its own component.
- **`ListsComponents`** (`Milpa\Live\Contracts\Component\ListsComponents`) — `names(): list<string>`,
  implemented by `InMemoryComponentRegistry` and the composite. A layer that does not list is still
  resolved but takes no part in conflict detection or `names()`.
- **`ComponentRendererRegistry::registerFor()` / `resolveFor()`** — the pair of the composite (a
  `milpa/live-web` `LiveEndpoint` or `XhtmlComponentCompiler` accepts it in place of a name-keyed array):
  a renderer registered *for* a component name answers for that name at its target, else `null` —
  exactly what the array answered. The target-wide `register()`/`resolve()` is a separate question
  and is deliberately **not** the fallback: every shipped HTML renderer is single-family and throws
  for the rest, so a fallback would hand a plugin's component to a renderer that refuses it and turn
  a missing registration into an uncaught exception in the endpoint. A host with a general renderer
  registers it for each name it serves.

```php
use Milpa\Live\Runtime\CompositeComponentRegistry;
use Milpa\Live\Rendering\ComponentRendererRegistry;

$components = new CompositeComponentRegistry(['host' => $hostRegistry, 'billing' => $billingRegistry], writable: 'host');
$renderers = new ComponentRendererRegistry();
$renderers->registerFor('invoice-list', $billingHtmlRenderer); // implements DeclaresClientAssets → its .js/.css travel with every compile
```

### Upgrading

Everything in this section is **additive**: no existing contract changes shape. `RenderResult`
gained an optional trailing constructor parameter (`clientAssets`) with a default, so every
existing renderer compiles; `InMemoryComponentRegistry` gained `names()`;
`ComponentRendererRegistry` gained `registerFor()`/`resolveFor()`. Nothing is required of a
renderer that has no client files to declare.

## Requirements

- PHP **≥ 8.3**
- `milpa/core` **≥ 0.9, < 1.0**
- `psr/log` **^3**

## Documentation

**Full API reference: [getmilpa.github.io/live](https://getmilpa.github.io/live/)** —
generated straight from the source DocBlocks and dressed with the Milpa design system.

## Contributing

Contributions are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). Please report security
issues via [SECURITY.md](SECURITY.md), and note that this project follows a
[Code of Conduct](CODE_OF_CONDUCT.md).

## License

[Apache-2.0](LICENSE) © Rodrigo Vicente - TeamX Agency.

---

Milpa is designed, built, and maintained by **[Rodrigo Vicente - TeamX Agency](https://teamx.agency/?utm_source=github&utm_medium=readme&utm_campaign=milpa&utm_content=live)**.
