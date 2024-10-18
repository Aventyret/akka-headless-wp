import * as esbuild from 'esbuild'
import base from './base.mjs'

await esbuild.build({
  ...base
})
