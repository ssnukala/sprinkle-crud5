const Encore = require("@symfony/webpack-encore");

// List dependent sprinkles and local entries files
const sprinkles = {
  Admin: require("@userfrosting/sprinkle-admin/webpack.entries"),
  AdminLTE: require("@userfrosting/theme-adminlte/webpack.entries"),
  FormGenerator: require("@lcharette/formgenerator/webpack.entries"),
  CRUD5: require("./webpack.entries"),
};

// Merge dependent Sprinkles entries with local entries
let entries = {};
Object.values(sprinkles).forEach((sprinkle) => {
  entries = Object.assign(entries, sprinkle);
});

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.UF_MODE || "dev");
}

Encore.setOutputPath("public/assets")
  .setPublicPath("/assets/")
  .addEntries(entries)
  .splitEntryChunks()
  .enableSingleRuntimeChunk()
  .cleanupOutputBeforeBuild()
  .enableBuildNotifications()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .configureBabelPresetEnv((config) => {
    config.useBuiltIns = "usage";
    config.corejs = 3;
  })
  .enableSassLoader()
  .autoProvidejQuery();

module.exports = Encore.getWebpackConfig();
