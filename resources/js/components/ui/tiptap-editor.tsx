import { useEffect } from 'react';
import type { ChangeEvent } from 'react';
import Image from '@tiptap/extension-image';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/react';
import { Button } from '@/components/ui/button';

type TiptapEditorProps = {
    value: string;
    onChange: (value: string) => void;
    minHeightClassName?: string;
    enableImageUpload?: boolean;
};

/**
 * Rich text editor backed by TipTap.
 */
export default function TiptapEditor({
    value,
    onChange,
    minHeightClassName = 'min-h-[260px]',
    enableImageUpload = false,
}: TiptapEditorProps) {
    const editor = useEditor({
        extensions: [
            StarterKit,
            Image.configure({
                inline: false,
                allowBase64: true,
            }),
        ],
        content: value || '',
        onUpdate: ({ editor: tiptapEditor }) => {
            onChange(tiptapEditor.getHTML());
        },
        editorProps: {
            attributes: {
                class: `w-full rounded-md border border-input bg-background p-3 text-sm focus:outline-none ${minHeightClassName}`,
            },
        },
    });

    useEffect(() => {
        if (!editor) {
            return;
        }

        const currentHtml = editor.getHTML();
        if (currentHtml !== value) {
            editor.commands.setContent(value || '', false);
        }
    }, [editor, value]);

    const insertImage = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (!file || !editor) {
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            const imageSrc = typeof reader.result === 'string' ? reader.result : '';
            if (imageSrc === '') {
                return;
            }

            editor.chain().focus().setImage({ src: imageSrc }).run();
        };
        reader.readAsDataURL(file);
        event.target.value = '';
    };

    if (!editor) {
        return null;
    }

    return (
        <div className="rounded-md border border-sidebar-border/70 p-3">
            <div className="mb-2 flex flex-wrap gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => editor.chain().focus().toggleBold().run()}
                >
                    B
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => editor.chain().focus().toggleItalic().run()}
                >
                    I
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => editor.chain().focus().toggleHeading({ level: 1 }).run()}
                >
                    H1
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}
                >
                    H2
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => editor.chain().focus().toggleBulletList().run()}
                >
                    UL
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => editor.chain().focus().toggleOrderedList().run()}
                >
                    OL
                </Button>
                {enableImageUpload && (
                    <label className="inline-flex items-center">
                        <input
                            type="file"
                            accept="image/*"
                            className="hidden"
                            onChange={insertImage}
                        />
                        <span className="inline-flex h-8 cursor-pointer items-center rounded-md border border-input bg-background px-3 text-sm hover:bg-muted">
                            Dodajte sliku
                        </span>
                    </label>
                )}
            </div>

            <EditorContent editor={editor} />
        </div>
    );
}
