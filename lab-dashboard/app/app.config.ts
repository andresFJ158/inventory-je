export default defineAppConfig({
  ui: {
    colors: {
      primary: 'emerald',
      gray: 'slate'
    },
    button: {
      default: {
        color: 'white',
        variant: 'solid'
      },
      rounded: 'rounded-lg'
    },
    card: {
      rounded: 'rounded-lg',
      shadow: 'shadow-sm',
      body: {
        background: 'bg-white'
      }
    },
    modal: {
      background: 'bg-white'
    },
    input: {
      rounded: 'rounded-lg',
      color: {
        white: {
          outline: 'bg-white border border-gray-300 text-gray-900 placeholder-gray-500 focus:ring-emerald-500 focus:border-emerald-500'
        }
      }
    },
    select: {
      rounded: 'rounded-lg',
      color: {
        white: {
          outline: 'bg-white border border-gray-300 text-gray-900 placeholder-gray-500 focus:ring-emerald-500 focus:border-emerald-500'
        }
      }
    },
    textarea: {
      rounded: 'rounded-lg',
      color: {
        white: {
          outline: 'bg-white border border-gray-300 text-gray-900 placeholder-gray-500 focus:ring-emerald-500 focus:border-emerald-500'
        }
      }
    }
  }
})
