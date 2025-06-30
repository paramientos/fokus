import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
import Link from '@tiptap/extension-link'
import TaskList from '@tiptap/extension-task-list'
import TaskItem from '@tiptap/extension-task-item'
import Table from '@tiptap/extension-table'
import TableRow from '@tiptap/extension-table-row'
import TableCell from '@tiptap/extension-table-cell'
import TableHeader from '@tiptap/extension-table-header'

document.addEventListener('DOMContentLoaded', () => {
  const editorElements = document.querySelectorAll('.markdown-editor')

  editorElements.forEach(element => {
    const content = element.querySelector('.content')
    const input = element.querySelector('.input')

    if (!content || !input) return

    const editor = new Editor({
      element: content,
      extensions: [
        StarterKit,
        Placeholder.configure({
          placeholder: input.getAttribute('placeholder') || '',
          emptyEditorClass: 'is-editor-empty',
        }),
        Link.configure({
          openOnClick: false,
          HTMLAttributes: {
            class: 'text-primary underline'
          }
        }),
        TaskList.configure({
          HTMLAttributes: {
            class: 'task-list'
          }
        }),
        TaskItem.configure({
          HTMLAttributes: {
            class: 'task-item'
          }
        }),
        Table.configure({
          resizable: true,
          HTMLAttributes: {
            class: 'table table-zebra w-full'
          }
        }),
        TableRow,
        TableHeader,
        TableCell,
      ],
      content: input.value,
      onUpdate: ({ editor }) => {
        input.value = editor.getHTML()
        input.dispatchEvent(new Event('input', { bubbles: true }))
      },
      editorProps: {
        attributes: {
          class: 'outline-none min-h-[15rem] w-full',
        }
      }
    })

    // Toolbar buttons
    const toolbar = element.querySelector('.toolbar')
    if (toolbar) {
      // Heading
      toolbar.querySelector('[data-action="heading"]')?.addEventListener('click', () => {
        editor.chain().focus().toggleHeading({ level: 2 }).run()
      })

      // Bold
      toolbar.querySelector('[data-action="bold"]')?.addEventListener('click', () => {
        editor.chain().focus().toggleBold().run()
      })

      // Italic
      toolbar.querySelector('[data-action="italic"]')?.addEventListener('click', () => {
        editor.chain().focus().toggleItalic().run()
      })

      // Bullet List
      toolbar.querySelector('[data-action="bullet-list"]')?.addEventListener('click', () => {
        editor.chain().focus().toggleBulletList().run()
      })

      // Ordered List
      toolbar.querySelector('[data-action="ordered-list"]')?.addEventListener('click', () => {
        editor.chain().focus().toggleOrderedList().run()
      })

      // Task List
      toolbar.querySelector('[data-action="task-list"]')?.addEventListener('click', () => {
        editor.chain().focus().toggleTaskList().run()
      })

      // Code Block
      toolbar.querySelector('[data-action="code-block"]')?.addEventListener('click', () => {
        editor.chain().focus().toggleCodeBlock().run()
      })

      // Blockquote
      toolbar.querySelector('[data-action="blockquote"]')?.addEventListener('click', () => {
        editor.chain().focus().toggleBlockquote().run()
      })

      // Horizontal Rule
      toolbar.querySelector('[data-action="horizontal-rule"]')?.addEventListener('click', () => {
        editor.chain().focus().setHorizontalRule().run()
      })

      // Link
      toolbar.querySelector('[data-action="link"]')?.addEventListener('click', () => {
        const url = window.prompt('URL')
        if (url) {
          editor.chain().focus().setLink({ href: url }).run()
        } else {
          editor.chain().focus().unsetLink().run()
        }
      })
    }
  })
})
