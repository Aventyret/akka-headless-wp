import * as esbuild from 'esbuild'
import base from './base.mjs'

const ctx = await esbuild.context({
  ...base
})

await ctx.watch()
console.log('watching...')
