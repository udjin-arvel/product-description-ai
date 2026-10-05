import { createFileRoute } from "@tanstack/react-router";
import { useEffect, useRef, useState, type ChangeEvent, type DragEvent, type FormEvent } from "react";
import { ArrowDown, ArrowRight, Check, Copy, ImagePlus, LoaderCircle, RotateCcw, Sparkles, Upload, X } from "lucide-react";
import { Button } from "@/components/ui/button";

export const Route = createFileRoute("/")({
  head: () => ({
    meta: [
      { title: "Студия описаний — продающие тексты для товаров" },
      { name: "description", content: "Загрузите фотографию и название товара, чтобы подготовить продающее описание. Демонстрационный режим." },
      { property: "og:title", content: "Студия описаний — продающие тексты для товаров" },
      { property: "og:description", content: "Из фотографии и названия товара — в готовое описание для карточки товара." },
      { property: "og:type", content: "website" },
      { name: "twitter:card", content: "summary_large_image" },
    ],
  }),
  component: Index,
});

const MAX_SIZE = 10 * 1024 * 1024;
const MAX_WORDS = 5000;
const API_BASE = (import.meta.env.VITE_API_URL as string | undefined)?.replace(/\/$/, "") ?? "http://localhost:8080";

type GenerateApiResponse = {
  description: string;
  keywords?: string[];
  message?: string;
};

function parseWordLimit(value: string): number | null | "invalid" {
  const trimmed = value.trim();
  if (trimmed === "") return null;
  if (!/^\d+$/.test(trimmed)) return "invalid";
  const parsed = Number(trimmed);
  if (parsed < 1 || parsed > MAX_WORDS) return "invalid";
  return parsed;
}

function Index() {
  const [name, setName] = useState("");
  const [minWords, setMinWords] = useState("");
  const [maxWords, setMaxWords] = useState("");
  const [file, setFile] = useState<File | null>(null);
  const [preview, setPreview] = useState<string | null>(null);
  const [dragging, setDragging] = useState(false);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState("");
  const [keywords, setKeywords] = useState<string[]>([]);
  const [generatedName, setGeneratedName] = useState("");
  const [copied, setCopied] = useState(false);
  const inputRef = useRef<HTMLInputElement>(null);
  const resultRef = useRef<HTMLElement>(null);

  useEffect(() => {
    if (!file) {
      setPreview(null);
      return;
    }
    const url = URL.createObjectURL(file);
    setPreview(url);
    return () => URL.revokeObjectURL(url);
  }, [file]);

  function resetResult() {
    setResult("");
    setKeywords([]);
  }

  function acceptFile(candidate?: File) {
    if (!candidate) return;
    if (!["image/jpeg", "image/png", "image/webp"].includes(candidate.type)) {
      setError("Выберите изображение в формате JPG, PNG или WebP.");
      return;
    }
    if (candidate.size > MAX_SIZE) {
      setError("Размер изображения не должен превышать 10 МБ.");
      return;
    }
    setFile(candidate);
    setError("");
    resetResult();
    if (inputRef.current) inputRef.current.value = "";
  }

  function onFileChange(event: ChangeEvent<HTMLInputElement>) {
    acceptFile(event.target.files?.[0]);
  }

  function onDrop(event: DragEvent<HTMLDivElement>) {
    event.preventDefault();
    setDragging(false);
    acceptFile(event.dataTransfer.files[0]);
  }

  async function generate(event?: FormEvent) {
    event?.preventDefault();
    if (!file) {
      setError("Сначала загрузите фотографию товара.");
      return;
    }
    if (!name.trim()) {
      setError("Добавьте название товара.");
      return;
    }

    const min = parseWordLimit(minWords);
    const max = parseWordLimit(maxWords);
    if (min === "invalid" || max === "invalid") {
      setError("Количество слов — целое число от 1 до 5000.");
      return;
    }
    if (min !== null && max !== null && min > max) {
      setError("Минимум слов не может превышать максимум.");
      return;
    }

    setError("");
    setCopied(false);
    setLoading(true);
    resetResult();

    const formData = new FormData();
    formData.append("title", name.trim());
    formData.append("image", file);
    if (min !== null) formData.append("min_words", String(min));
    if (max !== null) formData.append("max_words", String(max));

    try {
      const response = await fetch(`${API_BASE}/api/generate`, {
        method: "POST",
        body: formData,
      });

      const payload = (await response.json()) as GenerateApiResponse;

      if (!response.ok) {
        setError(payload.message ?? "Не удалось создать описание. Попробуйте ещё раз.");
        return;
      }

      setResult(payload.description ?? "");
      setKeywords(Array.isArray(payload.keywords) ? payload.keywords : []);
      setGeneratedName(name.trim());
      setTimeout(() => resultRef.current?.scrollIntoView({ behavior: "smooth", block: "start" }), 80);
    } catch {
      setError("Не удалось связаться с сервером. Проверьте, что демо API запущено.");
    } finally {
      setLoading(false);
    }
  }

  async function copyResult() {
    try {
      await navigator.clipboard.writeText(result);
      setCopied(true);
      setTimeout(() => setCopied(false), 2200);
    } catch {
      setError("Не удалось скопировать текст. Выделите его вручную.");
    }
  }

  return (
    <div className="min-h-screen bg-background text-foreground selection:bg-foreground selection:text-background">
      <header className="h-[74px] border-b border-line">
        <div className="mx-auto flex h-full max-w-[1160px] items-center justify-between px-5 sm:px-8">
          <a href="/" className="flex items-center gap-3 font-display text-[17px] font-semibold tracking-normal" aria-label="Студия описаний — главная">
            <span className="flex size-8 items-center justify-center rounded-md border border-border bg-panel-raised"><Sparkles className="size-[17px]" strokeWidth={1.8} /></span>
            Студия описаний<span className="text-subtle">.</span>
          </a>
          <span className="hidden items-center gap-2 text-xs font-medium text-muted-foreground sm:flex"><span className="size-1.5 rounded-full bg-success" /> Демо-режим</span>
        </div>
      </header>

      <main className="mx-auto max-w-[1160px] px-5 pb-24 sm:px-8">
        <div className="grid gap-10 border-b border-line pb-12 pt-14 lg:grid-cols-[1fr_340px] lg:items-end lg:gap-20 lg:pb-16 lg:pt-20">
          <div>
            <div className="mb-6 flex items-center gap-3 text-[11px] font-semibold uppercase text-muted-foreground"><span className="h-px w-6 bg-muted-foreground" /> Тексты для карточек товаров</div>
            <h1 className="max-w-[740px] font-display text-[clamp(2.7rem,5vw,4.8rem)] font-medium leading-[1.06] tracking-normal">Превратите товар<br />в историю, которую<br className="hidden sm:block" /> хочется купить<span className="text-subtle">.</span></h1>
          </div>
          <div className="lg:pb-2">
            <p className="max-w-[340px] text-[15px] leading-[1.8] text-muted-foreground">Одно фото и название — всё, что нужно для начала. Добавьте товар, а мы подготовим текст для его карточки.</p>
            <div className="mt-6 flex items-center gap-2 text-xs text-subtle"><ArrowDown className="size-3.5" /> Начните с загрузки изображения</div>
          </div>
        </div>

        <form onSubmit={generate} className="pt-10 lg:pt-12">
          <div className="mb-7 flex items-center justify-between gap-4">
            <div className="flex items-center gap-3"><span className="font-display text-sm text-subtle">01</span><h2 className="font-display text-xl font-medium">Ваш товар</h2></div>
            <span className="text-xs text-subtle">* Обязательные поля</span>
          </div>

          <div className="grid gap-6 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)] lg:gap-7">
            <div>
              <label htmlFor="product-image" className="mb-3 block text-sm font-medium">Изображение товара <span className="text-subtle">*</span></label>
              <input ref={inputRef} id="product-image" className="sr-only" type="file" accept="image/jpeg,image/png,image/webp" onChange={onFileChange} />
              <div
                onDragOver={(event) => { event.preventDefault(); setDragging(true); }}
                onDragLeave={(event) => { event.preventDefault(); setDragging(false); }}
                onDrop={onDrop}
                className={`group relative flex flex-col overflow-hidden rounded-md border border-dashed transition-colors ${preview ? "h-[min(72vh,560px)]" : "min-h-[310px] items-center justify-center sm:min-h-[350px]"} ${dragging ? "border-foreground bg-accent" : "border-border bg-panel hover:border-muted-foreground"}`}
              >
                {preview ? (
                  <>
                    <div className="flex min-h-0 w-full flex-1 items-center justify-center p-4 sm:p-5">
                      <img src={preview} alt={`Загруженное изображение: ${file?.name ?? "товар"}`} className="max-h-full max-w-full object-contain" />
                    </div>
                    <div className="flex w-full shrink-0 items-center justify-between gap-3 border-t border-border bg-panel px-4 py-3">
                      <div className="min-w-0"><p className="truncate text-xs font-medium">{file?.name}</p><p className="mt-0.5 text-[11px] text-muted-foreground">{file ? (file.size / 1024 / 1024).toFixed(1) : "0"} МБ</p></div>
                      <div className="flex shrink-0 gap-2">
                        <Button type="button" variant="quiet" size="icon" title="Заменить изображение" aria-label="Заменить изображение" onClick={() => inputRef.current?.click()}><Upload /></Button>
                        <Button type="button" variant="quiet" size="icon" title="Удалить изображение" aria-label="Удалить изображение" onClick={() => { setFile(null); resetResult(); }}><X /></Button>
                      </div>
                    </div>
                  </>
                ) : (
                  <div className="flex flex-col items-center px-6 text-center">
                    <div className="mb-6 flex size-16 items-center justify-center rounded-md border border-border bg-panel-raised text-foreground"><ImagePlus className="size-7" strokeWidth={1.4} /></div>
                    <p className="font-display text-lg font-medium">Перетащите изображение сюда</p>
                    <p className="mt-2 text-sm text-muted-foreground">или выберите файл на устройстве</p>
                    <Button type="button" variant="quiet" className="mt-6 h-10 px-5" onClick={() => inputRef.current?.click()}><Upload className="size-4" /> Выбрать файл</Button>
                    <p className="mt-5 text-xs text-subtle">JPG, PNG или WebP · до 10 МБ</p>
                  </div>
                )}
              </div>
            </div>

            <div className="flex flex-col">
              <label htmlFor="product-name" className="mb-3 block text-sm font-medium">Название товара <span className="text-subtle">*</span></label>
              <div className="flex flex-1 flex-col rounded-md border border-border bg-panel p-5 sm:p-6">
                <p className="mb-4 text-[11px] font-semibold uppercase text-subtle">Как называется ваш товар?</p>
                <textarea
                  id="product-name"
                  value={name}
                  onChange={(event) => { setName(event.target.value); setError(""); resetResult(); }}
                  placeholder="Например, керамическая ваза ручной работы"
                  maxLength={500}
                  className="min-h-[125px] w-full flex-1 resize-none bg-transparent font-display text-xl leading-relaxed text-foreground outline-none placeholder:text-subtle focus-visible:outline-none sm:text-2xl"
                />
                <div className="mt-auto border-t border-line pt-5 text-xs text-subtle">{name.length} / 500 символов</div>
              </div>
            </div>
          </div>

          <div className="mt-6 rounded-md border border-border bg-panel p-5 sm:p-6">
            <div className="mb-4 flex items-baseline justify-between gap-3">
              <p className="text-[11px] font-semibold uppercase text-subtle">Длина описания</p>
              <span className="text-xs text-subtle">Необязательно</span>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <label htmlFor="min-words" className="mb-2 block text-sm font-medium">Минимум слов</label>
                <input
                  id="min-words"
                  inputMode="numeric"
                  value={minWords}
                  onChange={(event) => { setMinWords(event.target.value); setError(""); resetResult(); }}
                  placeholder="Например, 80"
                  className="h-11 w-full rounded-md border border-border bg-background px-3 text-sm text-foreground outline-none placeholder:text-subtle focus-visible:outline-none"
                />
              </div>
              <div>
                <label htmlFor="max-words" className="mb-2 block text-sm font-medium">Максимум слов</label>
                <input
                  id="max-words"
                  inputMode="numeric"
                  value={maxWords}
                  onChange={(event) => { setMaxWords(event.target.value); setError(""); resetResult(); }}
                  placeholder="Например, 150"
                  className="h-11 w-full rounded-md border border-border bg-background px-3 text-sm text-foreground outline-none placeholder:text-subtle focus-visible:outline-none"
                />
              </div>
            </div>
            <p className="mt-4 text-xs text-subtle">Пустые поля не ограничивают объём текста.</p>
          </div>

          <div className="mt-7 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <p role="alert" className="min-h-5 text-sm text-destructive">{error}</p>
            <Button type="submit" variant="studio" disabled={loading} className="h-12 w-full px-7 text-sm font-semibold sm:w-auto">
              {loading ? <><LoaderCircle className="animate-spin" /> Создаём описание...</> : <><Sparkles /> Создать описание <ArrowRight className="ml-3" /></>}
            </Button>
          </div>
          <p className="mt-2 text-right text-xs text-subtle">Текст генерирует DeepSeek Vision по фото и названию товара</p>
        </form>

        {(loading || result) && (
          <section ref={resultRef} aria-live="polite" className="scroll-mt-8 animate-rise-in mt-16 border-t border-line pt-10 lg:mt-20 lg:pt-12">
            <div className="mb-7 flex flex-wrap items-center justify-between gap-4">
              <div className="flex items-center gap-3"><span className="font-display text-sm text-subtle">02</span><h2 className="font-display text-xl font-medium">Готовое описание</h2></div>
              {!loading && <span className="flex items-center gap-2 text-xs text-success"><Check className="size-3.5" /> Готово</span>}
            </div>
            <div className="grid overflow-hidden rounded-md border border-border bg-panel lg:grid-cols-[245px_1fr]">
              <div className="flex flex-col justify-between gap-12 border-b border-border bg-panel-raised p-6 lg:border-b-0 lg:border-r lg:p-7">
                <div>
                  <Sparkles className="mb-7 size-5 text-muted-foreground" strokeWidth={1.5} />
                  <p className="text-[11px] font-semibold uppercase text-subtle">Товар</p>
                  <p className="mt-2 break-words font-display text-lg font-medium leading-snug">{generatedName || name}</p>
                </div>
                <p className="text-xs leading-relaxed text-muted-foreground">Текст можно отредактировать перед публикацией.</p>
              </div>
              <div className="min-w-0 p-6 sm:p-8 lg:p-10">
                {loading ? (
                  <div className="flex min-h-[260px] flex-col justify-center gap-5" role="status">
                    <div className="flex items-center gap-3 text-sm text-muted-foreground"><LoaderCircle className="size-4 animate-spin" /> Подбираем слова для вашего товара...</div>
                    <div className="h-3 w-11/12 animate-pulse rounded-sm bg-accent" /><div className="h-3 w-9/12 animate-pulse rounded-sm bg-accent" /><div className="h-3 w-10/12 animate-pulse rounded-sm bg-accent" />
                  </div>
                ) : (
                  <>
                    <div className="mb-6 flex items-center justify-between gap-3 border-b border-line pb-5"><span className="text-[11px] font-semibold uppercase text-subtle">Текст для карточки товара</span><span className="text-xs text-subtle">{result.length} символов</span></div>
                    <label htmlFor="generated-copy" className="sr-only">Готовое описание товара</label>
                    <textarea id="generated-copy" value={result} onChange={(event) => setResult(event.target.value)} className="min-h-[250px] w-full resize-y bg-transparent text-[15px] leading-[1.9] text-foreground outline-none focus-visible:outline-none" />
                    {keywords.length > 0 && (
                      <div className="mt-6 border-t border-line pt-5">
                        <p className="text-[11px] font-semibold uppercase text-subtle">Ключевые слова</p>
                        <p className="mt-2 text-sm leading-relaxed text-muted-foreground">{keywords.join(", ")}</p>
                      </div>
                    )}
                    <div className="mt-7 flex flex-wrap gap-3 border-t border-line pt-6">
                      <Button type="button" variant="studio" onClick={copyResult} className="h-10 px-5">{copied ? <Check /> : <Copy />} {copied ? "Скопировано" : "Скопировать"}</Button>
                      <Button type="button" variant="quiet" onClick={() => generate()} className="h-10 px-5"><RotateCcw /> Создать заново</Button>
                    </div>
                  </>
                )}
              </div>
            </div>
          </section>
        )}
      </main>
      <footer className="border-t border-line"><div className="mx-auto flex max-w-[1160px] flex-wrap items-center justify-between gap-3 px-5 py-6 text-xs text-subtle sm:px-8"><span>Студия описаний.</span><span>Создано для идей, которые стоит показать миру.</span></div></footer>
    </div>
  );
}
