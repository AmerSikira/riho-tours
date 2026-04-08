import TiptapEditor from '@/components/ui/tiptap-editor';

type SimpleRichTextEditorProps = {
    value: string;
    onChange: (value: string) => void;
    minHeightClassName?: string;
};

/**
 * Backward-compatible wrapper that now uses TipTap.
 */
export default function SimpleRichTextEditor({
    value,
    onChange,
    minHeightClassName,
}: SimpleRichTextEditorProps) {
    return (
        <TiptapEditor
            value={value}
            onChange={onChange}
            minHeightClassName={minHeightClassName}
            enableImageUpload
        />
    );
}
